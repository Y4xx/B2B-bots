from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import List, Dict, Any, Optional
import logging
from embeddings import EmbeddingService
from vector_store import VectorStoreService
import os

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = FastAPI(title="SaaSBot AI Indexer Service", version="1.0.0")

# Initialize services
embedding_service = EmbeddingService(api_key=os.getenv("OPENAI_API_KEY"))
vector_store = VectorStoreService(
    host=os.getenv("QDRANT_HOST", "localhost"),
    port=int(os.getenv("QDRANT_PORT", "6333")),
    api_key=os.getenv("QDRANT_API_KEY")
)

class ChunkData(BaseModel):
    id: int
    content: str
    metadata: Dict[str, Any]

class IndexRequest(BaseModel):
    tenant_id: int
    site_id: int
    chunks: List[ChunkData]

class IndexedChunk(BaseModel):
    chunk_id: int
    vector_id: str

class IndexResponse(BaseModel):
    status: str
    indexed_chunks: List[IndexedChunk]
    total: int

@app.get("/")
async def root():
    return {
        "service": "SaaSBot AI Indexer",
        "version": "1.0.0",
        "status": "running"
    }

@app.get("/health")
async def health():
    return {"status": "healthy"}

@app.post("/index", response_model=IndexResponse)
async def index_chunks(request: IndexRequest):
    """
    Generate embeddings and index chunks in vector database
    """
    logger.info(f"Indexing {len(request.chunks)} chunks for tenant {request.tenant_id}, site {request.site_id}")
    
    try:
        indexed_chunks = []
        
        # Process chunks in batches
        batch_size = 10
        for i in range(0, len(request.chunks), batch_size):
            batch = request.chunks[i:i + batch_size]
            
            # Generate embeddings
            texts = [chunk.content for chunk in batch]
            embeddings = await embedding_service.generate_embeddings(texts)
            
            # Prepare points for vector store
            points = []
            for chunk, embedding in zip(batch, embeddings):
                point_id = f"{request.tenant_id}_{request.site_id}_{chunk.id}"
                
                # Add tenant_id and site_id to metadata for filtering
                metadata = chunk.metadata.copy()
                metadata['tenant_id'] = request.tenant_id
                metadata['site_id'] = request.site_id
                
                points.append({
                    'id': point_id,
                    'vector': embedding,
                    'payload': metadata
                })
                
                indexed_chunks.append(IndexedChunk(
                    chunk_id=chunk.id,
                    vector_id=point_id
                ))
            
            # Upsert to vector store
            await vector_store.upsert_points(
                collection_name=f"tenant_{request.tenant_id}",
                points=points
            )
            
            logger.info(f"Indexed batch of {len(batch)} chunks")
        
        return IndexResponse(
            status="success",
            indexed_chunks=indexed_chunks,
            total=len(indexed_chunks)
        )
        
    except Exception as e:
        logger.error(f"Error indexing chunks: {e}")
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/create-collection")
async def create_collection(tenant_id: int):
    """
    Create a vector collection for a tenant
    """
    try:
        collection_name = f"tenant_{tenant_id}"
        await vector_store.create_collection(collection_name)
        
        return {
            "status": "success",
            "collection": collection_name
        }
    except Exception as e:
        logger.error(f"Error creating collection: {e}")
        raise HTTPException(status_code=500, detail=str(e))

@app.delete("/delete-site")
async def delete_site_vectors(tenant_id: int, site_id: int):
    """
    Delete all vectors for a specific site
    """
    try:
        collection_name = f"tenant_{tenant_id}"
        await vector_store.delete_by_filter(
            collection_name=collection_name,
            filter_dict={"site_id": site_id}
        )
        
        return {
            "status": "success",
            "message": f"Deleted vectors for site {site_id}"
        }
    except Exception as e:
        logger.error(f"Error deleting site vectors: {e}")
        raise HTTPException(status_code=500, detail=str(e))

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
