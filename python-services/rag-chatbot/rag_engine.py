import openai
from typing import List, Dict, Any, Optional
from qdrant_client import QdrantClient
from qdrant_client.models import Filter, FieldCondition, MatchValue
import logging

logger = logging.getLogger(__name__)

class RAGEngine:
    def __init__(
        self,
        openai_api_key: str,
        qdrant_host: str = "localhost",
        qdrant_port: int = 6333,
        qdrant_api_key: Optional[str] = None
    ):
        self.openai_client = openai.OpenAI(api_key=openai_api_key)
        self.qdrant_client = QdrantClient(
            host=qdrant_host,
            port=qdrant_port,
            api_key=qdrant_api_key,
            timeout=60
        )
        self.model = "gpt-4-turbo-preview"
        self.embedding_model = "text-embedding-ada-002"
    
    async def query(
        self,
        tenant_id: int,
        site_id: int,
        query: str,
        history: List[Dict[str, str]] = None,
        max_results: int = 5
    ) -> Dict[str, Any]:
        """
        Process a query using RAG
        """
        try:
            # Step 1: Generate embedding for the query
            query_embedding = await self._generate_embedding(query)
            
            # Step 2: Search vector database
            search_results = await self._search_vectors(
                tenant_id=tenant_id,
                site_id=site_id,
                query_vector=query_embedding,
                limit=max_results
            )
            
            # Step 3: Build context from search results
            context = self._build_context(search_results)
            
            # Step 4: Generate answer using LLM
            answer, tokens = await self._generate_answer(
                query=query,
                context=context,
                history=history or []
            )
            
            # Step 5: Format sources
            sources = self._format_sources(search_results)
            
            return {
                'answer': answer,
                'sources': sources,
                'model': self.model,
                'tokens': tokens,
                'confidence': self._calculate_confidence(search_results)
            }
            
        except Exception as e:
            logger.error(f"Error in RAG query: {e}")
            raise
    
    async def _generate_embedding(self, text: str) -> List[float]:
        """Generate embedding for text"""
        try:
            response = self.openai_client.embeddings.create(
                model=self.embedding_model,
                input=text
            )
            return response.data[0].embedding
        except Exception as e:
            logger.error(f"Error generating embedding: {e}")
            raise
    
    async def _search_vectors(
        self,
        tenant_id: int,
        site_id: int,
        query_vector: List[float],
        limit: int = 5
    ) -> List[Dict[str, Any]]:
        """Search for similar vectors in Qdrant"""
        try:
            collection_name = f"tenant_{tenant_id}"
            
            # Build filter for site_id
            query_filter = Filter(
                must=[
                    FieldCondition(
                        key="site_id",
                        match=MatchValue(value=site_id)
                    )
                ]
            )
            
            # Search
            results = self.qdrant_client.search(
                collection_name=collection_name,
                query_vector=query_vector,
                limit=limit,
                query_filter=query_filter
            )
            
            # Format results
            formatted_results = []
            for result in results:
                formatted_results.append({
                    'id': result.id,
                    'score': result.score,
                    'content': result.payload.get('content', ''),
                    'url': result.payload.get('document_url', ''),
                    'title': result.payload.get('document_title', ''),
                    'metadata': result.payload
                })
            
            return formatted_results
            
        except Exception as e:
            logger.error(f"Error searching vectors: {e}")
            # Return empty results if search fails
            return []
    
    def _build_context(self, search_results: List[Dict[str, Any]], max_length: int = 4000) -> str:
        """Build context from search results"""
        context_parts = []
        current_length = 0
        
        for i, result in enumerate(search_results):
            content = result.get('content', '')
            title = result.get('title', 'Unknown')
            url = result.get('url', '')
            
            # Format context piece
            piece = f"[Source {i+1}]\nTitle: {title}\nURL: {url}\nContent: {content}\n\n"
            
            # Check length
            if current_length + len(piece) > max_length:
                break
            
            context_parts.append(piece)
            current_length += len(piece)
        
        return "".join(context_parts)
    
    async def _generate_answer(
        self,
        query: str,
        context: str,
        history: List[Dict[str, str]]
    ) -> tuple[str, int]:
        """Generate answer using LLM"""
        try:
            # Build system message
            system_message = """You are a helpful AI assistant that answers questions based on the provided context.
            
Rules:
1. Only answer based on the information in the context provided.
2. If the context doesn't contain enough information to answer the question, say so.
3. Be concise but thorough.
4. If you reference specific information, mention which source it came from.
5. Be friendly and professional.
"""
            
            # Build messages
            messages = [
                {"role": "system", "content": system_message}
            ]
            
            # Add history
            for msg in history[-5:]:  # Last 5 messages
                messages.append({
                    "role": msg.get('role', 'user'),
                    "content": msg.get('content', '')
                })
            
            # Add current query with context
            user_message = f"""Context:
{context}

Question: {query}

Please provide a helpful answer based on the context above."""
            
            messages.append({"role": "user", "content": user_message})
            
            # Generate response
            response = self.openai_client.chat.completions.create(
                model=self.model,
                messages=messages,
                temperature=0.7,
                max_tokens=500
            )
            
            answer = response.choices[0].message.content
            tokens = response.usage.total_tokens
            
            return answer, tokens
            
        except Exception as e:
            logger.error(f"Error generating answer: {e}")
            raise
    
    def _format_sources(self, search_results: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        """Format sources for response"""
        sources = []
        for result in search_results:
            content = result.get('content', '')
            excerpt = content[:200] + '...' if len(content) > 200 else content
            
            sources.append({
                'url': result.get('url', ''),
                'title': result.get('title', 'Unknown'),
                'excerpt': excerpt,
                'score': round(result.get('score', 0), 3)
            })
        
        return sources
    
    def _calculate_confidence(self, search_results: List[Dict[str, Any]]) -> Optional[float]:
        """Calculate confidence score based on search results"""
        if not search_results:
            return 0.0
        
        # Use average score of top results
        scores = [r.get('score', 0) for r in search_results[:3]]
        return round(sum(scores) / len(scores), 3) if scores else None
