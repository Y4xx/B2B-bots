import { useQuery } from '@tanstack/react-query'
import api from '../lib/api'
import DashboardLayout from '../components/dashboard/DashboardLayout'
import { Button } from '../components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../components/ui/card'
import { Check } from 'lucide-react'

export default function BillingPage() {
  const { data: subscription } = useQuery({
    queryKey: ['subscription'],
    queryFn: async () => {
      const response = await api.get('/billing/subscription')
      return response.data
    },
  })

  const plans = [
    {
      name: 'Basic',
      price: '$29',
      features: ['5 sites', '100 requests/min', 'Email support'],
    },
    {
      name: 'Pro',
      price: '$99',
      features: ['20 sites', '1,000 requests/min', 'Priority support', 'Custom branding'],
    },
    {
      name: 'Enterprise',
      price: '$299',
      features: ['100 sites', '10,000 requests/min', '24/7 support', 'Custom integration'],
    },
  ]

  return (
    <DashboardLayout>
      <div className="space-y-6">
        <div>
          <h1 className="text-3xl font-bold">Billing & Subscription</h1>
          <p className="text-gray-600">Manage your subscription and billing</p>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Current Plan</CardTitle>
            <CardDescription>
              You are currently on the {subscription?.plan} plan
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-2">
              <p>Status: {subscription?.status || 'Active'}</p>
              <p>Rate Limit: {subscription?.rate_limit} requests/min</p>
              {subscription?.trial_ends_at && (
                <p className="text-sm text-gray-500">
                  Trial ends: {new Date(subscription.trial_ends_at).toLocaleDateString()}
                </p>
              )}
            </div>
          </CardContent>
        </Card>

        <div className="grid md:grid-cols-3 gap-6">
          {plans.map((plan) => (
            <Card key={plan.name}>
              <CardHeader>
                <CardTitle>{plan.name}</CardTitle>
                <div className="text-3xl font-bold">{plan.price}<span className="text-sm font-normal">/month</span></div>
              </CardHeader>
              <CardContent>
                <ul className="space-y-2 mb-6">
                  {plan.features.map((feature) => (
                    <li key={feature} className="flex items-center">
                      <Check className="h-4 w-4 mr-2 text-green-600" />
                      <span className="text-sm">{feature}</span>
                    </li>
                  ))}
                </ul>
                <Button className="w-full">Choose Plan</Button>
              </CardContent>
            </Card>
          ))}
        </div>
      </div>
    </DashboardLayout>
  )
}
