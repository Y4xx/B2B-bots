# API Documentation

## Base URL

```
Production: https://api.saasbot.com
Development: http://localhost:8000/api
```

## Authentication

Most endpoints require authentication using Laravel Sanctum tokens.

**Headers:**
```
Authorization: Bearer {your-token}
Content-Type: application/json
```

## Response Format

### Success Response
```json
{
  "data": { ... },
  "message": "Success message (optional)"
}
```

### Error Response
```json
{
  "error": "Error message",
  "errors": {
    "field": ["Validation error messages"]
  }
}
```

## Endpoints

### Authentication

#### Register
Creates a new tenant account and user.

```http
POST /register
```

**Request:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "company_name": "Acme Inc" // optional
}
```

**Response:** `201 Created`
```json
{
  "user": {
    "id": 1,
    "tenant_id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "admin"
  },
  "tenant": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "plan": "free",
    "trial_ends_at": "2024-02-01T00:00:00Z",
    "is_active": true
  },
  "token": "1|abc123..."
}
```

#### Login
Authenticates a user and returns a token.

```http
POST /login
```

**Request:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response:** `200 OK`
```json
{
  "user": { ... },
  "tenant": { ... },
  "token": "2|xyz789..."
}
```

#### Logout
Revokes the current access token.

```http
POST /logout
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "message": "Logged out successfully"
}
```

#### Get Current User
Returns the authenticated user and tenant.

```http
GET /me
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "user": { ... },
  "tenant": { ... }
}
```

---

### Sites

#### List Sites
Get all sites for the authenticated tenant.

```http
GET /sites
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (optional): Page number for pagination
- `per_page` (optional): Results per page (default: 20)

**Response:** `200 OK`
```json
{
  "data": [
    {
      "id": 1,
      "tenant_id": 1,
      "name": "My Website",
      "domain": "example.com",
      "api_key": "sk_abc123...",
      "status": "active",
      "pages_crawled": 42,
      "documents_indexed": 42,
      "last_crawled_at": "2024-01-15T10:30:00Z",
      "last_indexed_at": "2024-01-15T11:00:00Z",
      "created_at": "2024-01-15T09:00:00Z",
      "updated_at": "2024-01-15T11:00:00Z"
    }
  ],
  "links": { ... },
  "meta": { ... }
}
```

#### Create Site
Add a new site to crawl and index.

```http
POST /sites
Authorization: Bearer {token}
```

**Request:**
```json
{
  "name": "My Website",
  "domain": "https://example.com",
  "crawl_config": {
    "max_pages": 100,
    "allowed_paths": ["/blog", "/docs"],
    "excluded_paths": ["/admin", "/private"]
  },
  "widget_config": {
    "theme": "light",
    "position": "bottom-right",
    "greeting": "Hi! How can I help you?"
  }
}
```

**Response:** `201 Created`
```json
{
  "id": 1,
  "tenant_id": 1,
  "name": "My Website",
  "domain": "example.com",
  "api_key": "sk_generated_key",
  "status": "pending",
  "pages_crawled": 0,
  "documents_indexed": 0,
  "crawl_config": { ... },
  "widget_config": { ... },
  "created_at": "2024-01-15T09:00:00Z",
  "updated_at": "2024-01-15T09:00:00Z"
}
```

#### Get Site
Get details of a specific site.

```http
GET /sites/{id}
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "id": 1,
  "tenant_id": 1,
  "name": "My Website",
  "domain": "example.com",
  "api_key": "sk_abc123...",
  "status": "active",
  "pages_crawled": 42,
  "documents_indexed": 42,
  "documents": [ ... ],
  "crawl_jobs": [ ... ]
}
```

#### Update Site
Update site configuration.

```http
PUT /sites/{id}
Authorization: Bearer {token}
```

**Request:**
```json
{
  "name": "Updated Name",
  "crawl_config": { ... },
  "widget_config": { ... }
}
```

**Response:** `200 OK`

#### Delete Site
Delete a site and all associated data.

```http
DELETE /sites/{id}
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "message": "Site deleted successfully"
}
```

#### Reindex Site
Trigger a new crawl and reindex.

```http
POST /sites/{id}/reindex
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "message": "Reindexing started",
  "site": { ... }
}
```

---

### Chat

#### Send Message
Send a chat message (public endpoint, authenticated via API key).

```http
POST /chat
```

**Request:**
```json
{
  "api_key": "sk_site_api_key",
  "message": "What is your pricing?",
  "session_id": "optional-uuid",
  "visitor_metadata": {
    "user_agent": "...",
    "referrer": "..."
  }
}
```

**Response:** `200 OK`
```json
{
  "session_id": "uuid",
  "message": "Our pricing starts at $29/month...",
  "sources": [
    {
      "url": "https://example.com/pricing",
      "title": "Pricing",
      "excerpt": "Our pricing starts at..."
    }
  ],
  "metadata": {
    "model": "gpt-4-turbo-preview",
    "tokens": 450
  }
}
```

**Error Responses:**
- `401`: Invalid API key
- `503`: Chatbot not ready or service unavailable
- `429`: Rate limit exceeded

#### Get Conversations
List chat conversations for a site.

```http
GET /sites/{siteId}/conversations
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "data": [
    {
      "id": 1,
      "site_id": 1,
      "session_id": "uuid",
      "visitor_ip": "192.168.1.1",
      "messages": [ ... ],
      "created_at": "2024-01-15T10:00:00Z"
    }
  ]
}
```

#### Get Messages
Get messages for a conversation.

```http
GET /conversations/{conversationId}
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "id": 1,
  "site_id": 1,
  "session_id": "uuid",
  "messages": [
    {
      "id": 1,
      "role": "user",
      "message": "Hello",
      "created_at": "2024-01-15T10:00:00Z"
    },
    {
      "id": 2,
      "role": "assistant",
      "message": "Hi! How can I help?",
      "sources": [ ... ],
      "created_at": "2024-01-15T10:00:05Z"
    }
  ]
}
```

---

### Billing

#### Get Current Subscription
Get subscription details for the current tenant.

```http
GET /billing/subscription
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "plan": "pro",
  "status": "active",
  "trial_ends_at": null,
  "is_on_trial": false,
  "has_active_subscription": true,
  "rate_limit": 1000
}
```

#### Create Checkout Session
Create a Stripe checkout session for upgrading.

```http
POST /billing/checkout
Authorization: Bearer {token}
```

**Request:**
```json
{
  "plan": "pro"
}
```

**Response:** `200 OK`
```json
{
  "checkout_url": "https://checkout.stripe.com/..."
}
```

#### Create Portal Session
Create a Stripe customer portal session.

```http
POST /billing/portal
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "portal_url": "https://billing.stripe.com/..."
}
```

---

### Webhooks

#### Scraper Callback
Receives crawled pages from Python scraper (internal).

```http
POST /webhooks/scraper
```

**Request:**
```json
{
  "site_id": 1,
  "tenant_id": 1,
  "status": "success",
  "pages": [
    {
      "url": "https://example.com/page",
      "title": "Page Title",
      "content": "Full page content...",
      "cleaned_content": "Cleaned content...",
      "metadata": { ... }
    }
  ]
}
```

#### Stripe Webhook
Receives Stripe events (verified by signature).

```http
POST /webhooks/stripe
Stripe-Signature: {signature}
```

**Handled Events:**
- `checkout.session.completed`
- `customer.subscription.updated`
- `customer.subscription.created`
- `customer.subscription.deleted`
- `invoice.payment_succeeded`
- `invoice.payment_failed`

---

## Rate Limiting

Rate limits apply per tenant based on subscription plan:

| Plan | Rate Limit |
|------|------------|
| Free | 10 requests/min |
| Basic | 100 requests/min |
| Pro | 1,000 requests/min |
| Enterprise | 10,000 requests/min |

**Headers:**
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
```

**Error:** `429 Too Many Requests`
```json
{
  "error": "Rate limit exceeded. Please upgrade your plan.",
  "retry_after": 60
}
```

## Pagination

Paginated endpoints return:

```json
{
  "data": [ ... ],
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  },
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 100
  }
}
```

## Error Codes

| Code | Description |
|------|-------------|
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden (tenant inactive or limit reached) |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Too Many Requests |
| 500 | Internal Server Error |
| 503 | Service Unavailable |
