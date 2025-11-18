# System Architecture

## Overview

SaaSBot is a multi-tenant SaaS platform that enables users to create AI-powered chatbots from their website content. The system uses a microservices architecture with the following components:

1. **Laravel Backend** - Multi-tenant API and orchestration
2. **React Frontend** - Admin dashboard
3. **React Widget** - Embeddable chat interface
4. **Python Scraper** - Web crawling service
5. **Python Indexer** - Embedding generation service
6. **Python RAG** - Retrieval-augmented generation chatbot
7. **Qdrant** - Vector database for semantic search
8. **MySQL** - Relational database
9. **Redis** - Queue and cache
10. **Nginx** - Reverse proxy

## Sequence Diagrams

### 1. Domain Indexing Process

```
User              Dashboard         Laravel API       Python Scraper    Python Indexer     Qdrant
 |                    |                 |                    |                 |              |
 |-- Add Domain ----->|                 |                    |                 |              |
 |                    |-- POST /sites ->|                    |                 |              |
 |                    |                 |-- Create Site      |                 |              |
 |                    |                 |-- Dispatch Job     |                 |              |
 |                    |<-- Site Created-|                    |                 |              |
 |<-- Site Created ---|                 |                    |                 |              |
 |                    |                 |                    |                 |              |
 |                    |                 |-- POST /scrape --->|                 |              |
 |                    |                 |                    |-- Start Crawl   |              |
 |                    |                 |                    |-- Fetch Pages   |              |
 |                    |                 |                    |-- Parse HTML    |              |
 |                    |                 |<-- POST callback --|                 |              |
 |                    |                 |-- Save Documents   |                 |              |
 |                    |                 |-- Dispatch Chunk   |                 |              |
 |                    |                 |-- Chunk Content    |                 |              |
 |                    |                 |-- POST /index -----|--------------->|              |
 |                    |                 |                    |                 |-- Generate  |
 |                    |                 |                    |                 |   Embeddings|
 |                    |                 |                    |                 |-- Upsert -->|
 |                    |                 |<-- Indexed --------|<---------------|              |
 |                    |                 |-- Update Status    |                 |              |
 |<-- Site Active ----|<-- GET /sites --|                    |                 |              |
```

**Steps:**

1. **User adds domain** via dashboard
2. **Dashboard sends POST /sites** to Laravel API
3. **Laravel creates Site record** with status "pending"
4. **Laravel dispatches StartCrawlJob** to queue
5. **Job calls Python Scraper** via POST /scrape
6. **Scraper crawls website**, respecting robots.txt and crawl config
7. **Scraper parses pages** using BeautifulSoup and Readability
8. **Scraper sends results** to Laravel webhook in batches
9. **Laravel creates Document records** for each page
10. **Laravel dispatches ChunkDocumentJob** for each document
11. **Job chunks content** into ~500-1000 token pieces
12. **Laravel dispatches IndexChunksJob**
13. **Job calls Python Indexer** with chunks
14. **Indexer generates embeddings** using OpenAI
15. **Indexer upserts vectors** to Qdrant
16. **Indexer returns vector IDs** to Laravel
17. **Laravel updates chunk records** with vector_ids
18. **Laravel marks site as "active"** when all documents indexed
19. **User sees active site** in dashboard

---

### 2. User Chat Process

```
Visitor          Widget          Laravel API      Python RAG       Qdrant        OpenAI
  |                |                  |                |               |             |
  |-- Open Chat -->|                  |                |               |             |
  |                |-- Load Widget    |                |               |             |
  |<-- Greeting ---|                  |                |               |             |
  |                |                  |                |               |             |
  |-- Type Msg --->|                  |                |               |             |
  |                |-- POST /chat --->|                |               |             |
  |                |   (api_key)      |-- Validate Key |               |             |
  |                |                  |-- Check Tenant |               |             |
  |                |                  |-- POST /query->|               |             |
  |                |                  |                |-- Embed Query |------------>|
  |                |                  |                |<-- Embedding--|             |
  |                |                  |                |-- Search ---->|             |
  |                |                  |                |<-- Results ---|             |
  |                |                  |                |-- Build Ctx   |             |
  |                |                  |                |-- Generate -->|------------>|
  |                |                  |                |<-- Answer ----|<------------|
  |                |                  |<-- Response ---|                |             |
  |                |                  |-- Save Message |               |             |
  |                |<-- Answer -------|                |               |             |
  |<-- Display ----|                  |                |               |             |
```

**Steps:**

1. **Visitor opens chat** on customer website
2. **Widget loads** from CDN
3. **Widget displays greeting** from config
4. **Visitor types message** and clicks send
5. **Widget sends POST /chat** with api_key and message
6. **Laravel validates API key** and finds Site
7. **Laravel checks tenant subscription** is active
8. **Laravel creates/finds Conversation** by session_id
9. **Laravel saves user message** to database
10. **Laravel calls Python RAG** service with query
11. **RAG generates query embedding** using OpenAI
12. **RAG searches Qdrant** with filters (tenant_id, site_id)
13. **RAG receives top K similar chunks** with scores
14. **RAG builds context** from search results
15. **RAG calls GPT-4** with context + query + history
16. **GPT-4 generates answer** based on context
17. **RAG returns answer + sources** to Laravel
18. **Laravel saves assistant message** with sources
19. **Laravel returns response** to widget
20. **Widget displays answer** with source links

---

### 3. Billing Process

```
User          Dashboard        Laravel API       Stripe           Webhook
 |                |                 |               |                |
 |-- Select Plan->|                 |               |                |
 |                |-- POST checkout>|               |                |
 |                |                 |-- Create ---->|                |
 |                |                 |    Session    |                |
 |                |<-- URL ---------|<-- URL -------|                |
 |<-- Redirect ---|                 |               |                |
 |                |                 |               |                |
 |-- Enter Card---------------------->|             |                |
 |-- Submit ----------------------------->|         |                |
 |<-- Success <----------------------------|         |                |
 |                |                 |               |                |
 |                |                 |               |-- Webhook ---->|
 |                |                 |<------------- POST /stripe ----|
 |                |                 |-- Verify Sig  |                |
 |                |                 |-- Update Plan |                |
 |                |                 |-- Update Rate |                |
 |                |<-- GET /me -----|               |                |
 |<-- Show Plan --|                 |               |                |
```

**Steps:**

1. **User selects plan** (Basic/Pro/Enterprise) in dashboard
2. **Dashboard calls POST /billing/checkout** with plan name
3. **Laravel creates Stripe Checkout Session** with metadata
4. **Laravel returns checkout URL** to dashboard
5. **Dashboard redirects user** to Stripe
6. **User enters payment** details on Stripe
7. **User completes payment**
8. **Stripe sends webhook** to Laravel (checkout.session.completed)
9. **Laravel verifies webhook** signature
10. **Laravel updates tenant** with stripe_customer_id
11. **Stripe sends subscription.created** webhook
12. **Laravel updates tenant plan** and status
13. **Laravel adjusts rate limits** based on new plan
14. **User redirected back** to dashboard
15. **Dashboard refreshes** and shows new plan

---

## Component Architecture

### Laravel Backend

```
┌─────────────────────────────────────────┐
│           Laravel Application            │
├─────────────────────────────────────────┤
│                                          │
│  ┌────────────┐      ┌────────────┐     │
│  │   Routes   │ ───> │Controllers │     │
│  └────────────┘      └────────────┘     │
│        │                    │            │
│        v                    v            │
│  ┌────────────┐      ┌────────────┐     │
│  │Middleware  │      │  Services  │     │
│  │- Auth      │      │- RAG       │     │
│  │- Tenant    │      │            │     │
│  │- RateLimit │      │            │     │
│  └────────────┘      └────────────┘     │
│        │                    │            │
│        v                    v            │
│  ┌────────────────────────────┐         │
│  │          Models            │         │
│  │ - Tenant (multi-tenancy)   │         │
│  │ - User                     │         │
│  │ - Site                     │         │
│  │ - Document                 │         │
│  │ - Chunk                    │         │
│  └────────────────────────────┘         │
│               │                          │
│               v                          │
│  ┌────────────────────────────┐         │
│  │           Jobs             │         │
│  │ - StartCrawlJob            │         │
│  │ - ChunkDocumentJob         │         │
│  │ - IndexChunksJob           │         │
│  └────────────────────────────┘         │
│               │                          │
└───────────────┼──────────────────────────┘
                │
                v
        ┌───────────────┐
        │     MySQL     │
        └───────────────┘
```

### Python Microservices

```
┌─────────────────────────────────────────┐
│         Python Scraper Service           │
├─────────────────────────────────────────┤
│                                          │
│  FastAPI Routes                          │
│    ↓                                     │
│  Crawler (async)                         │
│    - URL queue                           │
│    - Sitemap parsing                     │
│    - Respect robots.txt                  │
│    ↓                                     │
│  Parser                                  │
│    - BeautifulSoup                       │
│    - Readability                         │
│    - Text cleaning                       │
│    ↓                                     │
│  Callback to Laravel                     │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│         Python Indexer Service           │
├─────────────────────────────────────────┤
│                                          │
│  FastAPI Routes                          │
│    ↓                                     │
│  Embedding Service                       │
│    - OpenAI API                          │
│    - text-embedding-ada-002              │
│    - Batch processing                    │
│    ↓                                     │
│  Vector Store Service                    │
│    - Qdrant client                       │
│    - Collection management               │
│    - Metadata filtering                  │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│           Python RAG Service             │
├─────────────────────────────────────────┤
│                                          │
│  FastAPI Routes                          │
│    ↓                                     │
│  RAG Engine                              │
│    - Query embedding                     │
│    - Vector search (Qdrant)              │
│    - Context building                    │
│    - LLM generation (GPT-4)              │
│    - Source formatting                   │
└─────────────────────────────────────────┘
```

### Data Flow

```
┌──────────┐        ┌──────────┐        ┌──────────┐
│  MySQL   │<------>│  Laravel │<------>│  Redis   │
└──────────┘        └──────────┘        └──────────┘
                          ↕
                    ┌──────────┐
                    │  Python  │
                    │ Services │
                    └──────────┘
                          ↕
                    ┌──────────┐
                    │  Qdrant  │
                    └──────────┘
                          ↕
                    ┌──────────┐
                    │  OpenAI  │
                    └──────────┘
```

---

## Deployment Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                          Internet                            │
└─────────────────────────────────────────────────────────────┘
                             │
                             ↓
┌─────────────────────────────────────────────────────────────┐
│                      Load Balancer / CDN                     │
└─────────────────────────────────────────────────────────────┘
                             │
                    ┌────────┴────────┐
                    │                 │
                    ↓                 ↓
         ┌─────────────────┐  ┌─────────────────┐
         │      Nginx      │  │   Widget CDN    │
         │  Reverse Proxy  │  │  (Static Files) │
         └─────────────────┘  └─────────────────┘
                    │
        ┌───────────┼───────────┐
        │           │           │
        ↓           ↓           ↓
┌─────────────┐ ┌─────────────┐ ┌─────────────┐
│   React     │ │   Laravel   │ │   Python    │
│  Frontend   │ │   Backend   │ │  Services   │
│             │ │             │ │             │
│  (Port      │ │  (Port      │ │  (Ports     │
│   5173)     │ │   8000)     │ │   8001-3)   │
└─────────────┘ └─────────────┘ └─────────────┘
                    │                   │
        ┌───────────┼───────────────────┘
        │           │
        ↓           ↓
┌─────────────┐ ┌─────────────┐
│    MySQL    │ │   Qdrant    │
│  (Port      │ │  (Port      │
│   3306)     │ │   6333)     │
└─────────────┘ └─────────────┘
        │
        ↓
┌─────────────┐
│    Redis    │
│  (Port      │
│   6379)     │
└─────────────┘
```

---

## Security Considerations

1. **Multi-Tenancy Isolation**
   - All queries filtered by tenant_id
   - Middleware enforces tenant context
   - Separate vector collections per tenant

2. **Authentication**
   - Laravel Sanctum for API
   - API keys for public chat endpoint
   - Stripe signature verification

3. **Rate Limiting**
   - Per-tenant rate limits
   - Plan-based throttling
   - Redis-backed rate limiter

4. **Data Protection**
   - Encrypted passwords (bcrypt)
   - HTTPS only in production
   - CORS configuration
   - SQL injection protection

5. **API Security**
   - Token expiration
   - Input validation
   - XSS protection
   - CSRF protection
