# Project File Structure

Complete file tree of the SaaS AI Chatbot Platform.

## Root Directory

```
/saasbot/
├── .env.example                    # Global environment template
├── .gitignore                      # Git ignore patterns
├── docker-compose.yml              # Docker orchestration
├── README.md                       # Main project documentation
│
├── docs/                           # Documentation
│   ├── API.md                     # API endpoints reference
│   ├── ARCHITECTURE.md            # System architecture & diagrams
│   └── DEPLOYMENT.md              # Deployment guide
│
├── backend-laravel/               # Laravel 12 Backend
│   ├── .env.example              # Laravel environment template
│   ├── Dockerfile                # Laravel container
│   ├── composer.json             # PHP dependencies
│   │
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   └── Api/
│   │   │   │       ├── AuthController.php       # Authentication
│   │   │   │       ├── SiteController.php       # Site CRUD
│   │   │   │       ├── ChatController.php       # Chat API
│   │   │   │       ├── BillingController.php    # Stripe billing
│   │   │   │       └── WebhookController.php    # Webhooks
│   │   │   │
│   │   │   └── Middleware/
│   │   │       ├── EnsureTenantIsActive.php     # Tenancy middleware
│   │   │       └── TenantRateLimit.php          # Rate limiting
│   │   │
│   │   ├── Models/
│   │   │   ├── Tenant.php                       # Tenant model
│   │   │   ├── User.php                         # User model
│   │   │   ├── Site.php                         # Site model
│   │   │   ├── Document.php                     # Document model
│   │   │   ├── DocumentChunk.php                # Chunk model
│   │   │   ├── ChatConversation.php             # Conversation model
│   │   │   ├── ChatMessage.php                  # Message model
│   │   │   └── CrawlJob.php                     # Crawl job model
│   │   │
│   │   ├── Jobs/
│   │   │   ├── StartCrawlJob.php                # Initiate crawl
│   │   │   ├── ChunkDocumentJob.php             # Chunk content
│   │   │   ├── IndexChunksJob.php               # Index to Qdrant
│   │   │   └── ProcessDocumentJob.php           # Process pipeline
│   │   │
│   │   └── Services/
│   │       └── RagChatService.php               # RAG service wrapper
│   │
│   ├── database/
│   │   └── migrations/
│   │       ├── 2024_01_01_000001_create_tenants_table.php
│   │       ├── 2024_01_01_000002_create_users_table.php
│   │       ├── 2024_01_01_000003_create_sites_table.php
│   │       ├── 2024_01_01_000004_create_documents_table.php
│   │       ├── 2024_01_01_000005_create_document_chunks_table.php
│   │       ├── 2024_01_01_000006_create_chat_conversations_table.php
│   │       ├── 2024_01_01_000007_create_chat_messages_table.php
│   │       └── 2024_01_01_000008_create_crawl_jobs_table.php
│   │
│   ├── routes/
│   │   └── api.php                              # API routes
│   │
│   └── config/
│       ├── cors.php                             # CORS config
│       └── services.php                         # Service config
│
├── frontend-react/                # React + TypeScript Frontend
│   ├── Dockerfile                # Production build
│   ├── Dockerfile.dev            # Development build
│   ├── nginx.conf                # Nginx config for frontend
│   ├── package.json              # Node dependencies
│   ├── vite.config.ts            # Vite configuration
│   ├── tsconfig.json             # TypeScript config
│   ├── tailwind.config.js        # Tailwind CSS config
│   ├── postcss.config.js         # PostCSS config
│   ├── index.html                # HTML entry point
│   │
│   └── src/
│       ├── main.tsx              # React entry point
│       ├── App.tsx               # App component with routing
│       ├── index.css             # Global styles
│       │
│       ├── components/
│       │   ├── ui/               # Shadcn UI components
│       │   │   ├── button.tsx
│       │   │   ├── card.tsx
│       │   │   ├── input.tsx
│       │   │   └── label.tsx
│       │   │
│       │   └── dashboard/
│       │       └── DashboardLayout.tsx          # Layout component
│       │
│       ├── pages/
│       │   ├── LoginPage.tsx                    # Login page
│       │   ├── RegisterPage.tsx                 # Registration page
│       │   ├── DashboardPage.tsx                # Main dashboard
│       │   ├── SitesPage.tsx                    # Sites list
│       │   ├── AddSitePage.tsx                  # Add new site
│       │   ├── SiteDetailPage.tsx               # Site details
│       │   ├── ConversationsPage.tsx            # Chat history
│       │   ├── BillingPage.tsx                  # Billing & plans
│       │   └── SettingsPage.tsx                 # Settings
│       │
│       ├── lib/
│       │   ├── api.ts                           # Axios config
│       │   └── utils.ts                         # Utility functions
│       │
│       ├── store/
│       │   └── authStore.ts                     # Zustand auth state
│       │
│       └── types/
│           └── index.ts                         # TypeScript types
│
├── widget/                        # Embeddable Chat Widget
│   ├── package.json              # Widget dependencies
│   ├── vite.config.ts            # Vite config (IIFE build)
│   ├── tsconfig.json             # TypeScript config
│   ├── index.html                # Demo page
│   │
│   └── src/
│       └── widget.tsx            # Widget React component
│
├── python-services/              # Python Microservices
│   │
│   ├── scraper/                  # Web Scraper Service
│   │   ├── Dockerfile
│   │   ├── requirements.txt
│   │   ├── main.py              # FastAPI app
│   │   ├── crawler.py           # Web crawler
│   │   └── parser.py            # Content parser
│   │
│   ├── indexer/                  # AI Indexer Service
│   │   ├── Dockerfile
│   │   ├── requirements.txt
│   │   ├── main.py              # FastAPI app
│   │   ├── embeddings.py        # OpenAI embeddings
│   │   └── vector_store.py      # Qdrant client
│   │
│   └── rag-chatbot/              # RAG Chatbot Service
│       ├── Dockerfile
│       ├── requirements.txt
│       ├── main.py              # FastAPI app
│       └── rag_engine.py        # RAG logic
│
└── infra/                        # Infrastructure
    └── nginx/
        ├── nginx.conf           # Main nginx config
        └── conf.d/              # Additional configs
```

## File Count Summary

### Backend (Laravel)
- **Controllers**: 5 files
- **Middleware**: 2 files
- **Models**: 8 files
- **Jobs**: 4 files
- **Services**: 1 file
- **Migrations**: 8 files
- **Routes**: 1 file
- **Config**: 2 files
- **Docker**: 1 Dockerfile

**Total Backend Files**: ~32 files

### Frontend (React)
- **Pages**: 9 files
- **Components**: 5 files (4 UI + 1 layout)
- **Lib**: 2 files
- **Store**: 1 file
- **Types**: 1 file
- **Config**: 6 files (package.json, vite, tsconfig, tailwind, postcss, nginx)
- **Docker**: 2 Dockerfiles

**Total Frontend Files**: ~27 files

### Widget
- **Source**: 1 file (widget.tsx)
- **Config**: 3 files
- **Demo**: 1 file

**Total Widget Files**: ~5 files

### Python Services
- **Scraper**: 5 files (Dockerfile, requirements, main, crawler, parser)
- **Indexer**: 5 files (Dockerfile, requirements, main, embeddings, vector_store)
- **RAG**: 4 files (Dockerfile, requirements, main, rag_engine)

**Total Python Files**: ~14 files

### Infrastructure & Docs
- **Docker**: 1 docker-compose.yml
- **Nginx**: 1 nginx.conf
- **Docs**: 3 markdown files (API, ARCHITECTURE, DEPLOYMENT)
- **Root**: 3 files (README, .env.example, .gitignore)

**Total Infrastructure Files**: ~8 files

## Grand Total

**~86 source code files** across the entire project

## Key Configuration Files

| File | Purpose |
|------|---------|
| `.env.example` | Environment variables template |
| `docker-compose.yml` | Multi-container orchestration |
| `backend-laravel/composer.json` | PHP dependencies |
| `backend-laravel/routes/api.php` | API route definitions |
| `frontend-react/package.json` | Node.js dependencies |
| `frontend-react/vite.config.ts` | Vite build configuration |
| `widget/vite.config.ts` | Widget IIFE build config |
| `infra/nginx/nginx.conf` | Reverse proxy configuration |
| `python-services/*/requirements.txt` | Python dependencies |

## Database Schema

8 tables across the system:
1. `tenants` - Multi-tenant customers
2. `users` - Tenant users
3. `sites` - Registered websites
4. `documents` - Crawled pages
5. `document_chunks` - Text chunks
6. `chat_conversations` - Chat sessions
7. `chat_messages` - Individual messages
8. `crawl_jobs` - Background job tracking

## API Endpoints

- **Auth**: 4 endpoints (register, login, logout, me)
- **Sites**: 6 endpoints (list, create, get, update, delete, reindex)
- **Chat**: 3 endpoints (send message, conversations, messages)
- **Billing**: 3 endpoints (subscription, checkout, portal)
- **Webhooks**: 2 endpoints (scraper, stripe)

**Total API Endpoints**: ~18 endpoints

## Docker Services

1. Laravel App (PHP 8.2)
2. Laravel Horizon (Queue Worker)
3. MySQL (8.0)
4. Redis (7-alpine)
5. Python Scraper (FastAPI)
6. Python Indexer (FastAPI)
7. Python RAG (FastAPI)
8. Qdrant (Vector DB)
9. React Frontend (Vite dev server)
10. Nginx (Reverse Proxy)

**Total Services**: 10 containers

## Technology Stack

### Backend
- Laravel 12
- PHP 8.2
- MySQL 8.0
- Redis 7
- Laravel Sanctum
- Laravel Horizon
- Stripe PHP SDK

### Frontend
- React 18
- TypeScript 5
- Vite 5
- Shadcn UI
- Tailwind CSS 3
- TanStack Query
- Zustand
- Axios

### Widget
- React 18
- TypeScript 5
- Vite (IIFE build)
- Vanilla CSS

### Python
- Python 3.11
- FastAPI
- OpenAI API
- Qdrant Client
- BeautifulSoup4
- Readability
- httpx (async)

### Infrastructure
- Docker & Docker Compose
- Nginx (Alpine)
- Qdrant Vector Database

## Build Artifacts

After building, the following artifacts are created:

- `backend-laravel/vendor/` - Composer dependencies
- `frontend-react/dist/` - Production React build
- `frontend-react/node_modules/` - Node dependencies
- `widget/dist/widget.iife.js` - Widget bundle
- `python-services/*/venv/` - Python virtual environments

These are excluded from Git via `.gitignore`.
