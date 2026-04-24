import Dexie, { type EntityTable } from 'dexie'

export type OfflineDraft = {
  id: string
  kind: 'workout-session'
  payload: unknown
  updatedAt: string
}

export class LeverlyDatabase extends Dexie {
  drafts!: EntityTable<OfflineDraft, 'id'>

  constructor() {
    super('leverly')

    this.version(1).stores({
      drafts: 'id, kind, updatedAt',
    })
  }
}

export const leverlyDatabase = new LeverlyDatabase()
