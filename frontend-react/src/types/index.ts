export interface User {
  id: number
  tenant_id: number
  name: string
  email: string
  role: string
}

export interface Tenant {
  id: number
  name: string
  email: string
  company_name?: string
  plan: 'free' | 'basic' | 'pro' | 'enterprise'
  trial_ends_at?: string
  is_active: boolean
  settings?: Record<string, any>
}

export interface Site {
  id: number
  tenant_id: number
  name: string
  domain: string
  api_key: string
  status: 'pending' | 'crawling' | 'indexing' | 'active' | 'failed'
  pages_crawled: number
  documents_indexed: number
  last_crawled_at?: string
  last_indexed_at?: string
  crawl_config?: CrawlConfig
  widget_config?: WidgetConfig
  error_message?: string
  created_at: string
  updated_at: string
}

export interface CrawlConfig {
  max_pages: number
  allowed_paths: string[]
  excluded_paths: string[]
}

export interface WidgetConfig {
  theme: 'light' | 'dark'
  position: 'bottom-right' | 'bottom-left'
  greeting: string
}

export interface Document {
  id: number
  site_id: number
  url: string
  title?: string
  content: string
  status: string
  crawled_at?: string
  indexed_at?: string
}

export interface Conversation {
  id: number
  site_id: number
  session_id: string
  messages: Message[]
  created_at: string
}

export interface Message {
  id: number
  conversation_id: number
  role: 'user' | 'assistant'
  message: string
  sources?: Source[]
  created_at: string
}

export interface Source {
  url: string
  title: string
  excerpt: string
}

export interface Subscription {
  plan: string
  status?: string
  trial_ends_at?: string
  is_on_trial: boolean
  has_active_subscription: boolean
  rate_limit: number
}
