from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import List, Dict, Any, Optional
import logging
from rag_engine import RAGEngine
import os

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = FastAPI(title="SaaSBot RAG Chatbot Service", version="1.0.0")

# Initialize RAG engine
rag_engine = RAGEngine(
    openai_api_key=os.getenv("OPENAI_API_KEY"),
    qdrant_host=os.getenv("QDRANT_HOST", "localhost"),
    qdrant_port=int(os.getenv("QDRANT_PORT", "6333")),
    qdrant_api_key=os.getenv("QDRANT_API_KEY")
)

class Message(BaseModel):
    role: str
    content: str

class QueryRequest(BaseModel):
    tenant_id: int
    site_id: int
    query: str
    history: Optional[List[Message]] = []
    max_results: int = 5

class Source(BaseModel):
    url: str
    title: str
    excerpt: str
    score: float

class QueryResponse(BaseModel):
    answer: str
    sources: List[Source]
    model: str
    tokens: int
    confidence: Optional[float] = None

@app.get("/")
async def root():
    return {
        "service": "SaaSBot RAG Chatbot",
        "version": "1.0.0",
        "status": "running"
    }

@app.get("/health")
async def health():
    return {"status": "healthy"}

@app.post("/query", response_model=QueryResponse)
async def query(request: QueryRequest):
    """
    Process a user query using RAG
    """
    logger.info(f"Processing query for tenant {request.tenant_id}, site {request.site_id}")
    
    try:
        # Get response from RAG engine
        result = await rag_engine.query(
            tenant_id=request.tenant_id,
            site_id=request.site_id,
            query=request.query,
            history=request.history,
            max_results=request.max_results
        )
        
        # Format sources
        sources = []
        for source in result.get('sources', []):
            sources.append(Source(
                url=source['url'],
                title=source['title'],
                excerpt=source['excerpt'],
                score=source['score']
            ))
        
        return QueryResponse(
            answer=result['answer'],
            sources=sources,
            model=result['model'],
            tokens=result['tokens'],
            confidence=result.get('confidence')
        )
        
    except Exception as e:
        logger.error(f"Error processing query: {e}")
        raise HTTPException(status_code=500, detail=str(e))

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
