import { Routes, Route, Navigate } from 'react-router-dom'
import { useAuthStore } from './store/authStore'
import LoginPage from './pages/LoginPage'
import RegisterPage from './pages/RegisterPage'
import DashboardPage from './pages/DashboardPage'
import SitesPage from './pages/SitesPage'
import AddSitePage from './pages/AddSitePage'
import SiteDetailPage from './pages/SiteDetailPage'
import ConversationsPage from './pages/ConversationsPage'
import BillingPage from './pages/BillingPage'
import SettingsPage from './pages/SettingsPage'

function App() {
  const { token } = useAuthStore()

  return (
    <Routes>
      <Route path="/login" element={!token ? <LoginPage /> : <Navigate to="/dashboard" />} />
      <Route path="/register" element={!token ? <RegisterPage /> : <Navigate to="/dashboard" />} />
      
      <Route path="/dashboard" element={token ? <DashboardPage /> : <Navigate to="/login" />} />
      <Route path="/sites" element={token ? <SitesPage /> : <Navigate to="/login" />} />
      <Route path="/sites/add" element={token ? <AddSitePage /> : <Navigate to="/login" />} />
      <Route path="/sites/:id" element={token ? <SiteDetailPage /> : <Navigate to="/login" />} />
      <Route path="/conversations" element={token ? <ConversationsPage /> : <Navigate to="/login" />} />
      <Route path="/billing" element={token ? <BillingPage /> : <Navigate to="/login" />} />
      <Route path="/settings" element={token ? <SettingsPage /> : <Navigate to="/login" />} />
      
      <Route path="/" element={<Navigate to={token ? "/dashboard" : "/login"} />} />
    </Routes>
  )
}

export default App
