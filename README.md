# SaaSBot - AI Chatbot Platform

A complete SaaS platform for creating AI-powered chatbots from website domains.

## 🏗️ Architecture

This is a monorepo containing:

- **Backend API** (Laravel 12) - Multi-tenant SaaS backend with authentication, billing, and job queues
- **Frontend Dashboard** (React + TypeScript + Vite + Shadcn UI) - Admin interface for managing chatbots
- **Embeddable Widget** (React) - Lightweight chat widget for embedding on websites
- **Python Microservices** - FastAPI services for web scraping, AI indexing, and RAG chatbot
- **Vector Database** (Qdrant) - Semantic search and document retrieval

## 🚀 Quick Start

### Prerequisites

- Docker & Docker Compose
- Node.js 20+ (for local development)
- PHP 8.2+ (for local Laravel development)
- Python 3.11+ (for local Python development)

### Environment Setup

1. **Copy environment files:**
```bash
cp .env.example .env
cp backend-laravel/.env.example backend-laravel/.env
```

2. **Configure environment variables:**

Edit `.env` and set:
- `OPENAI_API_KEY` - Your OpenAI API key
- `STRIPE_KEY`, `STRIPE_SECRET` - Your Stripe keys
- Database credentials

### Running with Docker Compose

```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f

# Stop all services
docker-compose down
```

Services will be available at:
- Frontend: http://localhost:5173
- Laravel API: http://localhost:8000
- Python Scraper: http://localhost:8001
- Python Indexer: http://localhost:8002
- Python RAG: http://localhost:8003
- Qdrant: http://localhost:6333

## 📁 Project Structure

```
/saasbot/
├── backend-laravel/          # Laravel 12 API
│   ├── app/
│   │   ├── Models/          # Multi-tenant models
│   │   ├── Http/Controllers/# API controllers
│   │   ├── Jobs/            # Queue jobs (crawl, index)
│   │   └── Services/        # Business logic
│   ├── database/migrations/ # Database schema
│   └── routes/api.php       # API routes
│
├── frontend-react/           # React + TypeScript Dashboard
│   ├── src/
│   │   ├── components/ui/   # Shadcn UI components
│   │   ├── pages/          # Dashboard pages
│   │   └── store/          # Zustand state
│
├── widget/                   # Embeddable Chat Widget
│   └── src/widget.tsx       # React widget
│
├── python-services/
│   ├── scraper/             # Web crawler + parser
│   ├── indexer/             # Embedding generator
│   └── rag-chatbot/         # RAG engine
│
├── infra/nginx/             # Reverse proxy config
└── docker-compose.yml       # All services
```

## 🔄 How It Works

### 1. Domain Indexing
1. User adds website domain via dashboard
2. Laravel creates Site record and queues crawl job
3. Python scraper crawls pages and extracts content
4. Content is chunked and sent to indexer
5. Indexer generates embeddings using OpenAI
6. Embeddings stored in Qdrant vector database
7. Site marked as "active"

### 2. Chat Interaction
1. Widget loads on customer website
2. User sends message with site API key
3. Laravel validates and forwards to RAG service
4. RAG performs semantic search in Qdrant
5. GPT-4 generates contextual answer
6. Response returned with source citations

### 3. Multi-Tenancy
- All models include `tenant_id`
- Middleware enforces tenant isolation
- Rate limiting based on subscription plan
- Separate vector collections per tenant

## 📊 API Endpoints

### Authentication
```bash
POST /api/register    # Create account
POST /api/login       # Get auth token
POST /api/logout      # Invalidate token
GET  /api/me         # Current user
```

### Sites
```bash
GET    /api/sites           # List sites
POST   /api/sites           # Create site
GET    /api/sites/{id}      # Site details
PUT    /api/sites/{id}      # Update site
DELETE /api/sites/{id}      # Delete site
POST   /api/sites/{id}/reindex  # Trigger reindex
```

### Chat
```bash
POST /api/chat  # Send message (public with API key)
```

### Billing
```bash
GET  /api/billing/subscription  # Current plan
POST /api/billing/checkout      # Create checkout session
POST /api/billing/portal        # Customer portal
```

### Webhooks
```bash
POST /api/webhooks/scraper  # Python scraper callback
POST /api/webhooks/stripe   # Stripe events
```

## 🔧 Development

### Backend (Laravel)
```bash
cd backend-laravel
composer install
php artisan migrate
php artisan serve
php artisan horizon  # Queue worker
```

### Frontend (React)
```bash
cd frontend-react
npm install
npm run dev
```

### Widget
```bash
cd widget
npm install
npm run dev
npm run build  # Creates dist/widget.iife.js
```

### Python Services
```bash
cd python-services/scraper
pip install -r requirements.txt
uvicorn main:app --reload --port 8001
```

## 🎨 Widget Integration

Add to any website:

```html
<!-- Option 1: Auto-initialize with data attributes -->
<script 
  src="https://yourdomain.com/widget.js"
  data-api-key="sk_your_api_key"
  data-theme="light"
  data-position="bottom-right"
></script>

<!-- Option 2: Manual initialization -->
<script src="https://yourdomain.com/widget.js"></script>
<script>
  SaaSBot.initChatWidget({
    apiKey: 'sk_your_api_key',
    theme: 'light',
    position: 'bottom-right',
    greeting: 'How can I help you?'
  });
</script>
```

## 🗄️ Database Schema

### Core Tables
- `tenants` - SaaS customers
- `users` - Tenant users
- `sites` - Registered websites
- `documents` - Crawled pages
- `document_chunks` - Text chunks with embeddings
- `chat_conversations` - Chat sessions
- `chat_messages` - Individual messages
- `crawl_jobs` - Background job tracking

## 🔐 Security Features

- Laravel Sanctum for API authentication
- Multi-tenancy with tenant isolation
- Rate limiting per subscription tier
- API key validation for public chat endpoint
- Stripe webhook signature verification
- CORS configuration
- SQL injection protection via Eloquent

## 📈 Scalability

- Horizontal scaling via Docker Compose
- Queue-based processing (Redis + Horizon)
- Vector database for efficient search
- CDN for widget distribution
- Database indexing on tenant_id
- Async Python services

## 🛠️ Tech Stack

**Backend:**
- Laravel 12 (PHP 8.2)
- MySQL 8.0
- Redis
- Laravel Horizon
- Stripe API

**Frontend:**
- React 18
- TypeScript
- Vite
- Shadcn UI
- TanStack Query
- Zustand
- Tailwind CSS

**Python:**
- FastAPI
- OpenAI API
- Qdrant Client
- BeautifulSoup
- Readability

**Infrastructure:**
- Docker & Docker Compose
- Nginx
- Qdrant Vector DB

## 📄 License

MIT License

## 🤝 Contributing

Contributions welcome! Please open an issue first to discuss changes.

## 📧 Support

For questions and support, please open a GitHub issue.
