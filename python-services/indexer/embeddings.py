import openai
from typing import List
import logging

logger = logging.getLogger(__name__)

class EmbeddingService:
    def __init__(self, api_key: str, model: str = "text-embedding-ada-002"):
        self.api_key = api_key
        self.model = model
        openai.api_key = api_key
    
    async def generate_embeddings(self, texts: List[str]) -> List[List[float]]:
        """
        Generate embeddings for a list of texts
        """
        try:
            client = openai.OpenAI(api_key=self.api_key)
            
            # Clean and prepare texts
            cleaned_texts = [self._prepare_text(text) for text in texts]
            
            # Generate embeddings
            response = client.embeddings.create(
                model=self.model,
                input=cleaned_texts
            )
            
            # Extract embeddings
            embeddings = [item.embedding for item in response.data]
            
            logger.info(f"Generated {len(embeddings)} embeddings")
            return embeddings
            
        except Exception as e:
            logger.error(f"Error generating embeddings: {e}")
            raise
    
    def _prepare_text(self, text: str, max_tokens: int = 8000) -> str:
        """
        Prepare text for embedding (truncate if needed)
        """
        # Simple truncation - could use tiktoken for more accurate counting
        if len(text) > max_tokens * 4:  # Rough estimate: 1 token ≈ 4 chars
            text = text[:max_tokens * 4]
        
        return text.strip()
