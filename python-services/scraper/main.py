from fastapi import FastAPI, BackgroundTasks, HTTPException
from pydantic import BaseModel, HttpUrl
from typing import List, Optional, Dict, Any
import httpx
import asyncio
from crawler import WebCrawler
from parser import ContentParser
import logging

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = FastAPI(title="SaaSBot Web Scraper Service", version="1.0.0")

class CrawlConfig(BaseModel):
    max_pages: int = 100
    allowed_paths: List[str] = []
    excluded_paths: List[str] = []

class ScrapeRequest(BaseModel):
    domain: HttpUrl
    tenant_id: int
    site_id: int
    callback_url: HttpUrl
    config: Optional[CrawlConfig] = CrawlConfig()

class PageData(BaseModel):
    url: str
    title: Optional[str]
    content: str
    cleaned_content: str
    metadata: Dict[str, Any]

@app.get("/")
async def root():
    return {
        "service": "SaaSBot Web Scraper",
        "version": "1.0.0",
        "status": "running"
    }

@app.get("/health")
async def health():
    return {"status": "healthy"}

@app.post("/scrape")
async def scrape(request: ScrapeRequest, background_tasks: BackgroundTasks):
    """
    Start crawling a domain and send results to callback URL
    """
    logger.info(f"Received scrape request for {request.domain} (site_id: {request.site_id})")
    
    # Add crawl task to background
    background_tasks.add_task(
        crawl_and_callback,
        str(request.domain),
        request.tenant_id,
        request.site_id,
        str(request.callback_url),
        request.config
    )
    
    return {
        "status": "accepted",
        "message": "Crawl job started",
        "site_id": request.site_id
    }

async def crawl_and_callback(
    domain: str,
    tenant_id: int,
    site_id: int,
    callback_url: str,
    config: CrawlConfig
):
    """
    Crawl the domain and send results to callback URL
    """
    crawler = WebCrawler(domain, config)
    parser = ContentParser()
    
    try:
        logger.info(f"Starting crawl for {domain}")
        pages = await crawler.crawl()
        
        logger.info(f"Crawled {len(pages)} pages from {domain}")
        
        # Parse and clean content
        parsed_pages = []
        for page in pages:
            try:
                parsed = parser.parse(page['url'], page['html'])
                parsed_pages.append({
                    'url': page['url'],
                    'title': parsed['title'],
                    'content': parsed['text'],
                    'cleaned_content': parsed['cleaned_text'],
                    'metadata': {
                        'description': parsed.get('description', ''),
                        'keywords': parsed.get('keywords', []),
                        'lang': parsed.get('lang', 'en'),
                        'headers': parsed.get('headers', {}),
                    }
                })
            except Exception as e:
                logger.error(f"Error parsing {page['url']}: {e}")
                continue
        
        # Send results to callback URL in batches
        batch_size = 10
        for i in range(0, len(parsed_pages), batch_size):
            batch = parsed_pages[i:i + batch_size]
            
            async with httpx.AsyncClient() as client:
                try:
                    response = await client.post(
                        callback_url,
                        json={
                            'tenant_id': tenant_id,
                            'site_id': site_id,
                            'status': 'success',
                            'pages': batch
                        },
                        timeout=30.0
                    )
                    response.raise_for_status()
                    logger.info(f"Sent batch of {len(batch)} pages to callback")
                except Exception as e:
                    logger.error(f"Error sending callback: {e}")
                    
            # Small delay between batches
            await asyncio.sleep(1)
        
        logger.info(f"Completed crawl for {domain}")
        
    except Exception as e:
        logger.error(f"Crawl failed for {domain}: {e}")
        
        # Send error to callback
        async with httpx.AsyncClient() as client:
            try:
                await client.post(
                    callback_url,
                    json={
                        'tenant_id': tenant_id,
                        'site_id': site_id,
                        'status': 'error',
                        'error': str(e)
                    },
                    timeout=30.0
                )
            except Exception as callback_error:
                logger.error(f"Error sending error callback: {callback_error}")

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
