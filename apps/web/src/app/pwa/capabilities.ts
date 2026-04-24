export type PwaCapabilityReport = {
  serviceWorker: boolean
  installPrompt: boolean
}

export function getPwaCapabilityReport(): PwaCapabilityReport {
  return {
    serviceWorker: 'serviceWorker' in navigator,
    installPrompt: 'BeforeInstallPromptEvent' in window,
  }
}
