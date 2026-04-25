export const leverlyBrand = {
  productName: 'Leverly',
  tagline: 'intelligent progression for bodyweight strength.',
  shortDescription:
    'Calisthenics-first training software for logging work, tracking readiness, and choosing the next sensible progression.',
} as const

export type LeverlyTonePrinciple = {
  readonly name: string
  readonly rule: string
  readonly avoid: string
}

export const leverlyTonePrinciples: readonly LeverlyTonePrinciple[] = [
  {
    name: 'Precise',
    rule: 'Name the training signal that changed: reps, hold time, form quality, pain, readiness, leverage, assistance, or load.',
    avoid: 'Vague hype, unexplained advice, or generic fitness motivation.',
  },
  {
    name: 'Calm',
    rule: 'Use short sentences that make the next action clear without sounding urgent or alarmist.',
    avoid: 'Fear, shame, pressure, or exaggerated confidence.',
  },
  {
    name: 'Actionable',
    rule: 'Pair every warning or empty state with the next useful step the athlete can take.',
    avoid: 'Dead-end errors, blame, or copy that only describes a problem.',
  },
  {
    name: 'Careful',
    rule: 'Treat pain and readiness as training inputs, not as labels about the athlete or their health.',
    avoid: 'Medical labels, clinical claims, or certainty about injury status.',
  },
] as const

const medicalDiagnosisLanguagePattern = /\b(diagnos(?:e|is)|cure|treatment|prescribe|healed)\b/i

export function hasMedicalDiagnosisLanguage(value: string): boolean {
  return medicalDiagnosisLanguagePattern.test(value)
}
