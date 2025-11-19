from qdrant_client import QdrantClient
from qdrant_client.models import Distance, VectorParams, PointStruct, Filter, FieldCondition, MatchValue
from typing import List, Dict, Any, Optional
import logging

logger = logging.getLogger(__name__)

class VectorStoreService:
    def __init__(self, host: str = "localhost", port: int = 6333, api_key: Optional[str] = None):
        self.client = QdrantClient(
            host=host,
            port=port,
            api_key=api_key,
            timeout=60
        )
        self.vector_size = 1536  # OpenAI ada-002 embedding size
    
    async def create_collection(self, collection_name: str):
        """
        Create a new collection in Qdrant
        """
        try:
            # Check if collection exists
            collections = self.client.get_collections()
            if any(c.name == collection_name for c in collections.collections):
                logger.info(f"Collection {collection_name} already exists")
                return
            
            # Create collection
            self.client.create_collection(
                collection_name=collection_name,
                vectors_config=VectorParams(
                    size=self.vector_size,
                    distance=Distance.COSINE
                )
            )
            
            logger.info(f"Created collection: {collection_name}")
            
        except Exception as e:
            logger.error(f"Error creating collection: {e}")
            raise
    
    async def upsert_points(self, collection_name: str, points: List[Dict[str, Any]]):
        """
        Upsert points into Qdrant collection
        """
        try:
            # Ensure collection exists
            await self.create_collection(collection_name)
            
            # Convert to PointStruct
            point_structs = []
            for point in points:
                point_structs.append(
                    PointStruct(
                        id=point['id'],
                        vector=point['vector'],
                        payload=point['payload']
                    )
                )
            
            # Upsert points
            self.client.upsert(
                collection_name=collection_name,
                points=point_structs
            )
            
            logger.info(f"Upserted {len(point_structs)} points to {collection_name}")
            
        except Exception as e:
            logger.error(f"Error upserting points: {e}")
            raise
    
    async def search(
        self,
        collection_name: str,
        query_vector: List[float],
        limit: int = 5,
        filter_dict: Optional[Dict[str, Any]] = None
    ) -> List[Dict[str, Any]]:
        """
        Search for similar vectors
        """
        try:
            # Build filter if provided
            query_filter = None
            if filter_dict:
                conditions = []
                for key, value in filter_dict.items():
                    conditions.append(
                        FieldCondition(
                            key=key,
                            match=MatchValue(value=value)
                        )
                    )
                query_filter = Filter(must=conditions)
            
            # Search
            results = self.client.search(
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
                    'payload': result.payload
                })
            
            return formatted_results
            
        except Exception as e:
            logger.error(f"Error searching: {e}")
            raise
    
    async def delete_by_filter(self, collection_name: str, filter_dict: Dict[str, Any]):
        """
        Delete points matching filter
        """
        try:
            conditions = []
            for key, value in filter_dict.items():
                conditions.append(
                    FieldCondition(
                        key=key,
                        match=MatchValue(value=value)
                    )
                )
            
            self.client.delete(
                collection_name=collection_name,
                points_selector=Filter(must=conditions)
            )
            
            logger.info(f"Deleted points from {collection_name} matching filter")
            
        except Exception as e:
            logger.error(f"Error deleting points: {e}")
            raise
