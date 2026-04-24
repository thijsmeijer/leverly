import { leverlyApiRequest, type ApiResponseBody } from '@/shared/api/leverlyApi/runtimeClient'

import type { DashboardApiStatus } from '../types'

type HealthTransport = ApiResponseBody<'/health', 'get'> | null

export const unavailableDashboardApiStatus: DashboardApiStatus = {
  checkedAt: null,
  detail: 'Status unavailable',
  label: 'API status pending',
  state: 'unavailable',
}

export async function fetchDashboardStatus(): Promise<DashboardApiStatus> {
  const response = await leverlyApiRequest('/health', 'get', {
    authenticated: false,
    errorMode: 'silent',
    notFoundMode: 'silent',
  })

  return mapDashboardStatus(response)
}

export function mapDashboardStatus(response: HealthTransport): DashboardApiStatus {
  if (!response) {
    return unavailableDashboardApiStatus
  }

  return {
    checkedAt: response.meta.timestamp,
    detail: `Version ${response.meta.api_version}`,
    label: 'API online',
    state: 'online',
  }
}
