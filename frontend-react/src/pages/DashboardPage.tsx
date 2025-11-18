import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import api from '../lib/api'
import { useAuthStore } from '../store/authStore'
import { Site } from '../types'
import { Button } from '../components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../components/ui/card'
import { Plus, BarChart3, MessageSquare, Settings } from 'lucide-react'
import DashboardLayout from '../components/dashboard/DashboardLayout'

export default function DashboardPage() {
  const { tenant } = useAuthStore()

  const { data: sites, isLoading } = useQuery<{ data: Site[] }>({
    queryKey: ['sites'],
    queryFn: async () => {
      const response = await api.get('/sites')
      return response.data
    },
  })

  const stats = {
    totalSites: sites?.data?.length || 0,
    activeSites: sites?.data?.filter((s) => s.status === 'active').length || 0,
    totalDocuments: sites?.data?.reduce((sum, s) => sum + s.documents_indexed, 0) || 0,
  }

  return (
    <DashboardLayout>
      <div className="space-y-6">
        <div>
          <h1 className="text-3xl font-bold">Dashboard</h1>
          <p className="text-gray-600">
            Welcome back, {tenant?.name}! You're on the {tenant?.plan} plan.
          </p>
        </div>

        {/* Stats */}
        <div className="grid gap-4 md:grid-cols-3">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Total Sites</CardTitle>
              <BarChart3 className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.totalSites}</div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Active Sites</CardTitle>
              <MessageSquare className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.activeSites}</div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Total Documents</CardTitle>
              <Settings className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.totalDocuments}</div>
            </CardContent>
          </Card>
        </div>

        {/* Quick Actions */}
        <Card>
          <CardHeader>
            <CardTitle>Quick Actions</CardTitle>
            <CardDescription>Get started with your AI chatbots</CardDescription>
          </CardHeader>
          <CardContent className="grid gap-4 md:grid-cols-2">
            <Link to="/sites/add">
              <Button className="w-full" variant="outline">
                <Plus className="mr-2 h-4 w-4" />
                Add New Site
              </Button>
            </Link>
            <Link to="/sites">
              <Button className="w-full" variant="outline">
                View All Sites
              </Button>
            </Link>
          </CardContent>
        </Card>

        {/* Recent Sites */}
        <Card>
          <CardHeader>
            <CardTitle>Recent Sites</CardTitle>
            <CardDescription>Your latest chatbot deployments</CardDescription>
          </CardHeader>
          <CardContent>
            {isLoading ? (
              <p className="text-gray-500">Loading...</p>
            ) : sites?.data && sites.data.length > 0 ? (
              <div className="space-y-4">
                {sites.data.slice(0, 5).map((site) => (
                  <Link
                    key={site.id}
                    to={`/sites/${site.id}`}
                    className="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50"
                  >
                    <div>
                      <p className="font-medium">{site.name}</p>
                      <p className="text-sm text-gray-500">{site.domain}</p>
                    </div>
                    <div className="text-right">
                      <span className={`px-2 py-1 text-xs rounded-full ${
                        site.status === 'active' ? 'bg-green-100 text-green-800' :
                        site.status === 'crawling' ? 'bg-blue-100 text-blue-800' :
                        site.status === 'failed' ? 'bg-red-100 text-red-800' :
                        'bg-yellow-100 text-yellow-800'
                      }`}>
                        {site.status}
                      </span>
                      <p className="text-sm text-gray-500 mt-1">
                        {site.documents_indexed} documents
                      </p>
                    </div>
                  </Link>
                ))}
              </div>
            ) : (
              <div className="text-center py-8">
                <p className="text-gray-500 mb-4">No sites yet</p>
                <Link to="/sites/add">
                  <Button>
                    <Plus className="mr-2 h-4 w-4" />
                    Add Your First Site
                  </Button>
                </Link>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </DashboardLayout>
  )
}
