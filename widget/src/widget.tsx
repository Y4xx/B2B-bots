import React, { useState, useEffect, useRef } from 'react'
import ReactDOM from 'react-dom/client'

interface Message {
  role: 'user' | 'assistant'
  message: string
  sources?: Array<{
    url: string
    title: string
    excerpt: string
  }>
}

interface WidgetConfig {
  apiKey: string
  apiUrl?: string
  theme?: 'light' | 'dark'
  position?: 'bottom-right' | 'bottom-left'
  greeting?: string
}

function ChatWidget({ config }: { config: WidgetConfig }) {
  const [isOpen, setIsOpen] = useState(false)
  const [messages, setMessages] = useState<Message[]>([])
  const [input, setInput] = useState('')
  const [loading, setLoading] = useState(false)
  const [sessionId] = useState(() => `session_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`)
  const messagesEndRef = useRef<HTMLDivElement>(null)

  const apiUrl = config.apiUrl || 'http://localhost:8000'
  const theme = config.theme || 'light'
  const position = config.position || 'bottom-right'
  const greeting = config.greeting || 'Hi! How can I help you today?'

  useEffect(() => {
    if (isOpen && messages.length === 0) {
      setMessages([
        {
          role: 'assistant',
          message: greeting,
        },
      ])
    }
  }, [isOpen, greeting, messages.length])

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' })
  }, [messages])

  const sendMessage = async () => {
    if (!input.trim() || loading) return

    const userMessage = input.trim()
    setInput('')
    setMessages((prev) => [...prev, { role: 'user', message: userMessage }])
    setLoading(true)

    try {
      const response = await fetch(`${apiUrl}/api/chat`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          api_key: config.apiKey,
          message: userMessage,
          session_id: sessionId,
        }),
      })

      const data = await response.json()

      if (response.ok) {
        setMessages((prev) => [
          ...prev,
          {
            role: 'assistant',
            message: data.message,
            sources: data.sources,
          },
        ])
      } else {
        setMessages((prev) => [
          ...prev,
          {
            role: 'assistant',
            message: 'Sorry, I encountered an error. Please try again.',
          },
        ])
      }
    } catch (error) {
      setMessages((prev) => [
        ...prev,
        {
          role: 'assistant',
          message: 'Sorry, I encountered an error. Please try again.',
        },
      ])
    } finally {
      setLoading(false)
    }
  }

  const handleKeyPress = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault()
      sendMessage()
    }
  }

  const positionClasses = position === 'bottom-right' ? 'right-4 bottom-4' : 'left-4 bottom-4'

  return (
    <div className={`fixed ${positionClasses} z-50`} style={{ fontFamily: 'system-ui, -apple-system, sans-serif' }}>
      {/* Chat bubble button */}
      {!isOpen && (
        <button
          onClick={() => setIsOpen(true)}
          className="w-14 h-14 rounded-full shadow-lg flex items-center justify-center"
          style={{
            backgroundColor: theme === 'dark' ? '#1f2937' : '#3b82f6',
            color: 'white',
          }}
        >
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
          </svg>
        </button>
      )}

      {/* Chat window */}
      {isOpen && (
        <div
          className="rounded-lg shadow-2xl flex flex-col"
          style={{
            width: '380px',
            height: '600px',
            backgroundColor: theme === 'dark' ? '#1f2937' : 'white',
            border: theme === 'dark' ? '1px solid #374151' : '1px solid #e5e7eb',
          }}
        >
          {/* Header */}
          <div
            className="p-4 rounded-t-lg flex justify-between items-center"
            style={{
              backgroundColor: theme === 'dark' ? '#111827' : '#3b82f6',
              color: 'white',
            }}
          >
            <div>
              <h3 className="font-semibold">Chat Support</h3>
              <p className="text-xs opacity-80">We're here to help</p>
            </div>
            <button onClick={() => setIsOpen(false)} className="text-white hover:opacity-80">
              <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                <path d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" />
              </svg>
            </button>
          </div>

          {/* Messages */}
          <div
            className="flex-1 overflow-y-auto p-4 space-y-4"
            style={{
              backgroundColor: theme === 'dark' ? '#1f2937' : '#f9fafb',
            }}
          >
            {messages.map((msg, idx) => (
              <div
                key={idx}
                className={`flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}
              >
                <div
                  className="max-w-xs p-3 rounded-lg"
                  style={{
                    backgroundColor: msg.role === 'user'
                      ? theme === 'dark' ? '#3b82f6' : '#3b82f6'
                      : theme === 'dark' ? '#374151' : 'white',
                    color: msg.role === 'user' ? 'white' : theme === 'dark' ? 'white' : '#111827',
                  }}
                >
                  <p className="text-sm whitespace-pre-wrap">{msg.message}</p>
                  {msg.sources && msg.sources.length > 0 && (
                    <div className="mt-2 pt-2 border-t border-gray-300 text-xs">
                      <p className="font-semibold mb-1">Sources:</p>
                      {msg.sources.map((source, i) => (
                        <div key={i} className="mb-1">
                          <a
                            href={source.url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="underline hover:opacity-80"
                          >
                            {source.title}
                          </a>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              </div>
            ))}
            {loading && (
              <div className="flex justify-start">
                <div
                  className="p-3 rounded-lg"
                  style={{
                    backgroundColor: theme === 'dark' ? '#374151' : 'white',
                  }}
                >
                  <div className="flex space-x-2">
                    <div className="w-2 h-2 rounded-full bg-gray-400 animate-bounce"></div>
                    <div className="w-2 h-2 rounded-full bg-gray-400 animate-bounce" style={{ animationDelay: '0.2s' }}></div>
                    <div className="w-2 h-2 rounded-full bg-gray-400 animate-bounce" style={{ animationDelay: '0.4s' }}></div>
                  </div>
                </div>
              </div>
            )}
            <div ref={messagesEndRef} />
          </div>

          {/* Input */}
          <div
            className="p-4 border-t"
            style={{
              borderColor: theme === 'dark' ? '#374151' : '#e5e7eb',
            }}
          >
            <div className="flex gap-2">
              <input
                type="text"
                value={input}
                onChange={(e) => setInput(e.target.value)}
                onKeyPress={handleKeyPress}
                placeholder="Type your message..."
                className="flex-1 px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-blue-500"
                style={{
                  backgroundColor: theme === 'dark' ? '#374151' : 'white',
                  borderColor: theme === 'dark' ? '#4b5563' : '#d1d5db',
                  color: theme === 'dark' ? 'white' : '#111827',
                }}
                disabled={loading}
              />
              <button
                onClick={sendMessage}
                disabled={loading || !input.trim()}
                className="px-4 py-2 rounded-lg text-white disabled:opacity-50"
                style={{
                  backgroundColor: '#3b82f6',
                }}
              >
                Send
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

// Widget initialization
export function initChatWidget(config: WidgetConfig) {
  const container = document.createElement('div')
  container.id = 'saasbot-widget-container'
  document.body.appendChild(container)

  const root = ReactDOM.createRoot(container)
  root.render(<ChatWidget config={config} />)
}

// Auto-initialize if data attribute is present
if (typeof window !== 'undefined') {
  const script = document.currentScript as HTMLScriptElement
  if (script) {
    const apiKey = script.getAttribute('data-api-key')
    if (apiKey) {
      window.addEventListener('DOMContentLoaded', () => {
        initChatWidget({
          apiKey,
          apiUrl: script.getAttribute('data-api-url') || undefined,
          theme: (script.getAttribute('data-theme') as 'light' | 'dark') || 'light',
          position: (script.getAttribute('data-position') as 'bottom-right' | 'bottom-left') || 'bottom-right',
          greeting: script.getAttribute('data-greeting') || undefined,
        })
      })
    }
  }
}

// Expose for manual initialization
;(window as any).SaaSBot = { initChatWidget }
