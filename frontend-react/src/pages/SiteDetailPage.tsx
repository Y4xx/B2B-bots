import DashboardLayout from '../components/dashboard/DashboardLayout'
import { Card, CardContent, CardHeader, CardTitle } from '../components/ui/card'

export default function SiteDetailPage() {
  return (
    <DashboardLayout>
      <div className="space-y-6">
        <h1 className="text-3xl font-bold">Site Details</h1>
        <Card>
          <CardHeader>
            <CardTitle>Site Information</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-gray-500">Site details and configuration coming soon...</p>
          </CardContent>
        </Card>
      </div>
    </DashboardLayout>
  )
}
