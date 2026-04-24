import { toTypedSchema } from '@vee-validate/zod'
import { z } from 'zod'

export const readinessSchema = z.object({
  readiness: z.number().int().min(1).max(5),
  pain: z.number().int().min(0).max(10),
  form: z.number().int().min(1).max(5),
})

export type ReadinessForm = z.infer<typeof readinessSchema>

export const readinessValidationSchema = toTypedSchema(readinessSchema)
