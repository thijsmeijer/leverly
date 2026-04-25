import { dashboardCopy } from '@/shared/brand'
import { leverlyApiRequest, type ApiResponseBody } from '@/shared/api/leverlyApi/runtimeClient'

import type { DashboardApiStatus } from '../types'

type HealthTransport = ApiResponseBody<'/health', 'get'> | null

export const unavailableDashboardApiStatus: DashboardApiStatus = {
  checkedAt: null,
  detail: dashboardCopy.connectionStatus.unavailableDetail,
  label: dashboardCopy.connectionStatus.unavailableLabel,
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
    detail: dashboardCopy.connectionStatus.onlineDetail(response.meta.api_version),
    label: dashboardCopy.connectionStatus.onlineLabel,
    state: 'online',
  }
}
