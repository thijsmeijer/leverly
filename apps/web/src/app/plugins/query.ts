import { QueryClient, type QueryClientConfig, VueQueryPlugin } from '@tanstack/vue-query'

const defaultQueryClientConfig = {
  defaultOptions: {
    queries: {
      refetchOnWindowFocus: false,
      retry: 1,
      staleTime: 30_000,
    },
  },
} satisfies QueryClientConfig

export function createLeverlyQueryClient(config: QueryClientConfig = {}) {
  return new QueryClient({
    ...defaultQueryClientConfig,
    ...config,
    defaultOptions: {
      ...defaultQueryClientConfig.defaultOptions,
      ...config.defaultOptions,
      queries: {
        ...defaultQueryClientConfig.defaultOptions.queries,
        ...config.defaultOptions?.queries,
      },
    },
  })
}

export const queryClient = createLeverlyQueryClient()

export { VueQueryPlugin }
