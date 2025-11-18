import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import api from '../lib/api'
import { Site } from '../types'
import { Button } from '../components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '../components/ui/card'
import { Plus, RefreshCw } from 'lucide-react'
import DashboardLayout from '../components/dashboard/DashboardLayout'
import { formatDate, getStatusColor } from '../lib/utils'

export default function SitesPage() {
  const { data: sites, isLoading, refetch } = useQuery<{ data: Site[] }>({
    queryKey: ['sites'],
    queryFn: async () => {
      const response = await api.get('/sites')
      return response.data
    },
  })

  return (
    <DashboardLayout>
      <div className="space-y-6">
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-3xl font-bold">Sites</h1>
            <p className="text-gray-600">Manage your AI chatbot deployments</p>
          </div>
          <div className="flex gap-2">
            <Button variant="outline" size="sm" onClick={() => refetch()}>
              <RefreshCw className="h-4 w-4 mr-2" />
              Refresh
            </Button>
            <Link to="/sites/add">
              <Button>
                <Plus className="h-4 w-4 mr-2" />
                Add Site
              </Button>
            </Link>
          </div>
        </div>

        {isLoading ? (
          <Card>
            <CardContent className="p-8 text-center">
              <p className="text-gray-500">Loading sites...</p>
            </CardContent>
          </Card>
        ) : sites?.data && sites.data.length > 0 ? (
          <div className="grid gap-6">
            {sites.data.map((site) => (
              <Card key={site.id} className="hover:shadow-lg transition-shadow">
                <CardHeader>
                  <div className="flex justify-between items-start">
                    <div>
                      <CardTitle className="text-xl">{site.name}</CardTitle>
                      <p className="text-sm text-gray-500 mt-1">{site.domain}</p>
                    </div>
                    <span className={`px-3 py-1 text-xs font-medium rounded-full ${getStatusColor(site.status)}`}>
                      {site.status}
                    </span>
                  </div>
                </CardHeader>
                <CardContent>
                  <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    <div>
                      <p className="text-sm text-gray-500">Pages Crawled</p>
                      <p className="text-lg font-semibold">{site.pages_crawled}</p>
                    </div>
                    <div>
                      <p className="text-sm text-gray-500">Documents Indexed</p>
                      <p className="text-lg font-semibold">{site.documents_indexed}</p>
                    </div>
                    <div>
                      <p className="text-sm text-gray-500">Last Crawled</p>
                      <p className="text-sm font-medium">{formatDate(site.last_crawled_at)}</p>
                    </div>
                    <div>
                      <p className="text-sm text-gray-500">Last Indexed</p>
                      <p className="text-sm font-medium">{formatDate(site.last_indexed_at)}</p>
                    </div>
                  </div>

                  {site.error_message && (
                    <div className="bg-red-50 text-red-600 p-3 rounded-md text-sm mb-4">
                      {site.error_message}
                    </div>
                  )}

                  <div className="flex gap-2">
                    <Link to={`/sites/${site.id}`}>
                      <Button variant="outline" size="sm">View Details</Button>
                    </Link>
                    {site.status === 'active' && (
                      <Button variant="outline" size="sm">
                        View Conversations
                      </Button>
                    )}
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        ) : (
          <Card>
            <CardContent className="p-12 text-center">
              <Globe className="mx-auto h-12 w-12 text-gray-400 mb-4" />
              <h3 className="text-lg font-medium mb-2">No sites yet</h3>
              <p className="text-gray-500 mb-4">
                Get started by adding your first website to create an AI chatbot
              </p>
              <Link to="/sites/add">
                <Button>
                  <Plus className="h-4 w-4 mr-2" />
                  Add Your First Site
                </Button>
              </Link>
            </CardContent>
          </Card>
        )}
      </div>
    </DashboardLayout>
  )
}
