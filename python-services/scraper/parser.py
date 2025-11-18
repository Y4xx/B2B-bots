from bs4 import BeautifulSoup
from readability import Document
import re
from typing import Dict

class ContentParser:
    def parse(self, url: str, html: str) -> Dict:
        """Parse and clean HTML content"""
        soup = BeautifulSoup(html, 'lxml')
        
        # Use readability to extract main content
        doc = Document(html)
        
        # Extract metadata
        title = self._extract_title(soup, doc)
        description = self._extract_description(soup)
        keywords = self._extract_keywords(soup)
        lang = self._extract_lang(soup)
        
        # Extract text
        text = self._extract_text(soup)
        cleaned_text = self._clean_text(text)
        
        # Extract headers
        headers = self._extract_headers(soup)
        
        return {
            'url': url,
            'title': title,
            'text': text,
            'cleaned_text': cleaned_text,
            'description': description,
            'keywords': keywords,
            'lang': lang,
            'headers': headers,
        }
    
    def _extract_title(self, soup: BeautifulSoup, doc: Document) -> str:
        """Extract page title"""
        # Try readability first
        title = doc.title()
        if title:
            return title
        
        # Try meta title
        title_tag = soup.find('title')
        if title_tag:
            return title_tag.get_text().strip()
        
        # Try h1
        h1 = soup.find('h1')
        if h1:
            return h1.get_text().strip()
        
        return ''
    
    def _extract_description(self, soup: BeautifulSoup) -> str:
        """Extract meta description"""
        meta_desc = soup.find('meta', attrs={'name': 'description'})
        if meta_desc and meta_desc.get('content'):
            return meta_desc['content'].strip()
        
        meta_og = soup.find('meta', attrs={'property': 'og:description'})
        if meta_og and meta_og.get('content'):
            return meta_og['content'].strip()
        
        return ''
    
    def _extract_keywords(self, soup: BeautifulSoup) -> list:
        """Extract meta keywords"""
        meta_keywords = soup.find('meta', attrs={'name': 'keywords'})
        if meta_keywords and meta_keywords.get('content'):
            keywords = meta_keywords['content'].split(',')
            return [k.strip() for k in keywords]
        return []
    
    def _extract_lang(self, soup: BeautifulSoup) -> str:
        """Extract language"""
        html_tag = soup.find('html')
        if html_tag and html_tag.get('lang'):
            return html_tag['lang']
        return 'en'
    
    def _extract_text(self, soup: BeautifulSoup) -> str:
        """Extract text content"""
        # Remove script and style elements
        for script in soup(["script", "style", "nav", "footer", "header"]):
            script.decompose()
        
        # Get text
        text = soup.get_text()
        return text
    
    def _clean_text(self, text: str) -> str:
        """Clean and normalize text"""
        # Break into lines and remove leading/trailing space
        lines = (line.strip() for line in text.splitlines())
        
        # Break multi-headlines into a line each
        chunks = (phrase.strip() for line in lines for phrase in line.split("  "))
        
        # Drop blank lines
        text = '\n'.join(chunk for chunk in chunks if chunk)
        
        # Remove extra whitespace
        text = re.sub(r'\s+', ' ', text)
        
        return text.strip()
    
    def _extract_headers(self, soup: BeautifulSoup) -> Dict:
        """Extract all headers"""
        headers = {}
        for i in range(1, 7):
            header_tags = soup.find_all(f'h{i}')
            if header_tags:
                headers[f'h{i}'] = [tag.get_text().strip() for tag in header_tags]
        return headers
