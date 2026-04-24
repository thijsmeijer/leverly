import { useQuery } from '@tanstack/vue-query'
import { computed } from 'vue'

import { fetchDashboardStatus, unavailableDashboardApiStatus } from '../api/dashboardStatusService'

export function useDashboardApiStatus() {
  const query = useQuery({
    queryKey: ['dashboard', 'api-status'],
    queryFn: fetchDashboardStatus,
  })

  return {
    apiStatus: computed(() => query.data.value ?? unavailableDashboardApiStatus),
    isRefreshing: computed(() => query.isFetching.value),
    refreshStatus: () => query.refetch(),
  }
}
