import DashboardLayout from '../components/dashboard/DashboardLayout'
import { Card, CardContent, CardHeader, CardTitle } from '../components/ui/card'

export default function SettingsPage() {
  return (
    <DashboardLayout>
      <div className="space-y-6">
        <h1 className="text-3xl font-bold">Settings</h1>
        <Card>
          <CardHeader>
            <CardTitle>Account Settings</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-gray-500">Settings page coming soon...</p>
          </CardContent>
        </Card>
      </div>
    </DashboardLayout>
  )
}
