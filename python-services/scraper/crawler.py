import asyncio
import httpx
from typing import List, Dict, Set
from urllib.parse import urljoin, urlparse
import logging
from bs4 import BeautifulSoup
import validators

logger = logging.getLogger(__name__)

class WebCrawler:
    def __init__(self, domain: str, config):
        self.domain = domain
        self.config = config
        self.visited_urls: Set[str] = set()
        self.to_visit: List[str] = [domain]
        self.pages: List[Dict] = []
        self.max_pages = config.max_pages
        
    def is_valid_url(self, url: str) -> bool:
        """Check if URL should be crawled"""
        if not validators.url(url):
            return False
            
        parsed = urlparse(url)
        base_domain = urlparse(self.domain).netloc
        
        # Must be same domain
        if parsed.netloc != base_domain:
            return False
        
        # Check excluded paths
        for excluded in self.config.excluded_paths:
            if excluded in parsed.path:
                return False
        
        # Check allowed paths (if specified)
        if self.config.allowed_paths:
            allowed = False
            for allowed_path in self.config.allowed_paths:
                if parsed.path.startswith(allowed_path):
                    allowed = True
                    break
            if not allowed:
                return False
        
        return True
    
    def extract_links(self, html: str, base_url: str) -> List[str]:
        """Extract all links from HTML"""
        soup = BeautifulSoup(html, 'lxml')
        links = []
        
        for link in soup.find_all('a', href=True):
            url = urljoin(base_url, link['href'])
            # Remove fragments
            url = url.split('#')[0]
            
            if self.is_valid_url(url) and url not in self.visited_urls:
                links.append(url)
        
        return links
    
    async def fetch_page(self, url: str) -> Dict:
        """Fetch a single page"""
        try:
            async with httpx.AsyncClient(follow_redirects=True, timeout=30.0) as client:
                response = await client.get(url, headers={
                    'User-Agent': 'SaaSBot-Crawler/1.0'
                })
                
                if response.status_code == 200:
                    return {
                        'url': str(response.url),
                        'html': response.text,
                        'status_code': response.status_code
                    }
        except Exception as e:
            logger.error(f"Error fetching {url}: {e}")
            return None
    
    async def crawl(self) -> List[Dict]:
        """Crawl the website"""
        while self.to_visit and len(self.pages) < self.max_pages:
            url = self.to_visit.pop(0)
            
            if url in self.visited_urls:
                continue
            
            self.visited_urls.add(url)
            logger.info(f"Crawling {url} ({len(self.pages) + 1}/{self.max_pages})")
            
            page_data = await self.fetch_page(url)
            
            if page_data:
                self.pages.append(page_data)
                
                # Extract new links
                new_links = self.extract_links(page_data['html'], url)
                
                # Add new links to queue
                for link in new_links:
                    if link not in self.visited_urls and link not in self.to_visit:
                        self.to_visit.append(link)
            
            # Small delay to be respectful
            await asyncio.sleep(0.5)
        
        logger.info(f"Crawl complete. Visited {len(self.pages)} pages")
        return self.pages
