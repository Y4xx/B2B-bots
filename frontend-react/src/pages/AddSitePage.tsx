import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useMutation } from '@tanstack/react-query'
import api from '../lib/api'
import DashboardLayout from '../components/dashboard/DashboardLayout'
import { Button } from '../components/ui/button'
import { Input } from '../components/ui/input'
import { Label } from '../components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../components/ui/card'

export default function AddSitePage() {
  const navigate = useNavigate()
  const [formData, setFormData] = useState({
    name: '',
    domain: '',
    crawl_config: {
      max_pages: 100,
      allowed_paths: [] as string[],
      excluded_paths: [] as string[],
    },
  })
  const [error, setError] = useState('')

  const createSite = useMutation({
    mutationFn: async (data: typeof formData) => {
      const response = await api.post('/sites', data)
      return response.data
    },
    onSuccess: (data) => {
      navigate(`/sites/${data.id}`)
    },
    onError: (err: any) => {
      setError(err.response?.data?.error || 'Failed to create site')
    },
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    setError('')
    createSite.mutate(formData)
  }

  return (
    <DashboardLayout>
      <div className="max-w-2xl">
        <h1 className="text-3xl font-bold mb-6">Add New Site</h1>

        <Card>
          <CardHeader>
            <CardTitle>Site Details</CardTitle>
            <CardDescription>
              Enter your website URL to create an AI chatbot
            </CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-6">
              {error && (
                <div className="bg-red-50 text-red-600 p-3 rounded-md text-sm">
                  {error}
                </div>
              )}

              <div className="space-y-2">
                <Label htmlFor="name">Site Name</Label>
                <Input
                  id="name"
                  placeholder="My Website"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  required
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="domain">Website URL</Label>
                <Input
                  id="domain"
                  type="url"
                  placeholder="https://example.com"
                  value={formData.domain}
                  onChange={(e) => setFormData({ ...formData, domain: e.target.value })}
                  required
                />
                <p className="text-sm text-gray-500">
                  We'll crawl this website to create your chatbot knowledge base
                </p>
              </div>

              <div className="space-y-2">
                <Label htmlFor="max_pages">Maximum Pages to Crawl</Label>
                <Input
                  id="max_pages"
                  type="number"
                  min="1"
                  max="10000"
                  value={formData.crawl_config.max_pages}
                  onChange={(e) =>
                    setFormData({
                      ...formData,
                      crawl_config: {
                        ...formData.crawl_config,
                        max_pages: parseInt(e.target.value) || 100,
                      },
                    })
                  }
                />
                <p className="text-sm text-gray-500">
                  Limit the number of pages to crawl (1-10,000)
                </p>
              </div>

              <div className="flex gap-3">
                <Button type="submit" disabled={createSite.isPending}>
                  {createSite.isPending ? 'Creating...' : 'Create Site'}
                </Button>
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => navigate('/sites')}
                >
                  Cancel
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </DashboardLayout>
  )
}
