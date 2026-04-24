export type DashboardApiConnectionState = 'online' | 'unavailable'

export interface DashboardApiStatus {
  readonly checkedAt: string | null
  readonly detail: string
  readonly label: string
  readonly state: DashboardApiConnectionState
}
