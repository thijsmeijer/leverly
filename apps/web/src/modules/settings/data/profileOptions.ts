import type { ChoiceOption } from '../types'

export type EquipmentOption = ChoiceOption & {
  readonly category: string
  readonly unlocks: string[]
}

export type EquipmentCategory = {
  readonly description: string
  readonly id: string
  readonly items: EquipmentOption[]
  readonly title: string
}

export type EquipmentPreset = {
  readonly description: string
  readonly equipment: string[]
  readonly label: string
}

export const unitSystemOptions: ChoiceOption[] = [
  { label: 'Metric', value: 'metric', description: 'Kilograms and centimeters.' },
  { label: 'Imperial', value: 'imperial', description: 'Pounds and familiar US units.' },
]

export const bodyweightUnitOptions: ChoiceOption[] = [
  { label: 'kg', value: 'kg' },
  { label: 'lb', value: 'lb' },
]

export const experienceLevelOptions: ChoiceOption[] = [
  { label: 'New', value: 'new', description: 'Still learning the basic shapes and control.' },
  { label: 'Beginner', value: 'beginner', description: 'Building consistency with core patterns.' },
  { label: 'Intermediate', value: 'intermediate', description: 'Progressing skills and harder leverage.' },
  { label: 'Advanced', value: 'advanced', description: 'Managing high-skill strength blocks.' },
  { label: 'Elite', value: 'elite', description: 'Dialing in fine progression details.' },
]

export const goalOptions: ChoiceOption[] = [
  { label: 'Strength', value: 'strength', description: 'Move toward harder leverage and higher force.' },
  { label: 'Hypertrophy', value: 'hypertrophy', description: 'Build muscle with productive volume.' },
  { label: 'Skill', value: 'skill', description: 'Unlock technical calisthenics milestones.' },
  { label: 'Endurance', value: 'endurance', description: 'Handle more quality work over time.' },
  { label: 'General fitness', value: 'general_fitness', description: 'Stay capable, strong, and consistent.' },
  { label: 'Mobility', value: 'mobility', description: 'Improve positions and usable range.' },
  { label: 'Conditioning', value: 'conditioning', description: 'Add engine work without losing control.' },
]

export const compatibleSecondaryGoals: Record<string, string[]> = {
  conditioning: ['endurance', 'hypertrophy', 'general_fitness'],
  endurance: ['conditioning', 'general_fitness', 'mobility'],
  general_fitness: ['strength', 'endurance', 'mobility'],
  hypertrophy: ['strength', 'conditioning', 'mobility'],
  mobility: ['skill', 'strength', 'general_fitness'],
  skill: ['strength', 'mobility', 'endurance'],
  strength: ['skill', 'hypertrophy', 'mobility'],
}

export const equipmentCategories: EquipmentCategory[] = [
  {
    id: 'bars',
    title: 'Bars and stations',
    description: 'Anchor points for pulling, dips, support holds, leg raises, and scalable row work.',
    items: [
      {
        category: 'Bars and stations',
        label: 'Pull-up bar',
        value: 'pull_up_bar',
        description: 'Strict pulls, hangs, raises, muscle-up prep, and weighted pulling.',
        unlocks: ['Vertical pulling', 'Hanging core', 'Weighted strength'],
      },
      {
        category: 'Bars and stations',
        label: 'Low bar',
        value: 'low_bar',
        description: 'Rows, assisted pull-up paths, foot-supported skills, and beginner pulling volume.',
        unlocks: ['Horizontal pulling', 'Assisted progressions'],
      },
      {
        category: 'Bars and stations',
        label: 'Dip bars',
        value: 'dip_bars',
        description: 'Dips, support holds, L-sits, straight-bar alternatives, and pressing volume.',
        unlocks: ['Dips', 'Support strength', 'Core compression'],
      },
      {
        category: 'Bars and stations',
        label: 'Parallel bars',
        value: 'parallel_bars',
        description: 'Longer bar support work, swing basics, deep dips, and advanced skill practice.',
        unlocks: ['Support strength', 'Skill practice', 'Pressing volume'],
      },
      {
        category: 'Bars and stations',
        label: 'Stall bars',
        value: 'stall_bars',
        description: 'Leg raises, mobility drills, assisted positions, and controlled shoulder work.',
        unlocks: ['Hanging core', 'Mobility', 'Assisted progressions'],
      },
    ],
  },
  {
    id: 'skills',
    title: 'Skill supports',
    description: 'Portable tools for leverage work, wrist-friendly positions, and technical progressions.',
    items: [
      {
        category: 'Skill supports',
        label: 'Parallettes',
        value: 'parallettes',
        description: 'Planche leans, L-sits, handstand work, and wrist-friendly pressing.',
        unlocks: ['Skill practice', 'Wrist-friendly pressing', 'Core compression'],
      },
      {
        category: 'Skill supports',
        label: 'Rings',
        value: 'rings',
        description: 'Rows, dips, support holds, fly variations, false-grip work, and scalable pulling.',
        unlocks: ['Ring strength', 'Instability control', 'Scalable pulling'],
      },
      {
        category: 'Skill supports',
        label: 'Suspension trainer',
        value: 'suspension_trainer',
        description: 'Adjustable rows, assisted single-leg work, bodyline drills, and travel training.',
        unlocks: ['Scalable pulling', 'Travel setup', 'Assisted progressions'],
      },
      {
        category: 'Skill supports',
        label: 'Box or bench',
        value: 'box_bench',
        description: 'Step-ups, decline work, elevated push-ups, split squats, and regression options.',
        unlocks: ['Leg progressions', 'Pressing regressions', 'Assisted progressions'],
      },
    ],
  },
  {
    id: 'loading',
    title: 'Assistance and loading',
    description: 'Tools that make progressions easier to scale up, scale down, or load precisely.',
    items: [
      {
        category: 'Assistance and loading',
        label: 'Resistance band',
        value: 'resistance_band',
        description: 'Assisted pull-ups, activation, mobility, accommodating resistance, and warm-ups.',
        unlocks: ['Assistance', 'Warm-ups', 'Mobility'],
      },
      {
        category: 'Assistance and loading',
        label: 'Weight vest',
        value: 'weight_vest',
        description: 'Loaded push-ups, squats, lunges, rows, dips, and conditioning progressions.',
        unlocks: ['External load', 'Weighted strength'],
      },
      {
        category: 'Assistance and loading',
        label: 'Dip belt',
        value: 'dip_belt',
        description: 'Heavy pull-ups, dips, and progression work when bodyweight is no longer enough.',
        unlocks: ['Heavy pulling', 'Heavy dips', 'Weighted strength'],
      },
      {
        category: 'Assistance and loading',
        label: 'Weighted backpack',
        value: 'weighted_backpack',
        description: 'Accessible loading for home push-ups, squats, lunges, rows, and carries.',
        unlocks: ['External load', 'Home loading'],
      },
    ],
  },
  {
    id: 'conditioning',
    title: 'Core and conditioning',
    description: 'Small tools for trunk work, warm-ups, density blocks, and low-friction conditioning.',
    items: [
      {
        category: 'Core and conditioning',
        label: 'Ab wheel',
        value: 'ab_wheel',
        description: 'Anterior core, anti-extension strength, rollout progressions, and trunk control.',
        unlocks: ['Core strength', 'Anti-extension'],
      },
      {
        category: 'Core and conditioning',
        label: 'Jump rope',
        value: 'jump_rope',
        description: 'Warm-ups, conditioning finishers, footwork, and compact travel sessions.',
        unlocks: ['Conditioning', 'Warm-ups', 'Travel setup'],
      },
      {
        category: 'Core and conditioning',
        label: 'Training mat',
        value: 'training_mat',
        description: 'Floor-based core, mobility, hollow holds, cooldowns, and kneeling regressions.',
        unlocks: ['Core strength', 'Mobility', 'Comfort'],
      },
    ],
  },
]

export const equipmentOptions: ChoiceOption[] = equipmentCategories.flatMap((category) =>
  category.items.map(({ description, label, value }) => ({ description, label, value })),
)

export const equipmentPresets: EquipmentPreset[] = [
  {
    label: 'Minimal home',
    description: 'Compact setup for most beginner-to-intermediate home progressions.',
    equipment: ['pull_up_bar', 'resistance_band', 'parallettes', 'training_mat'],
  },
  {
    label: 'Park bars',
    description: 'Outdoor setup for pulling, dips, support holds, and skill practice.',
    equipment: ['pull_up_bar', 'low_bar', 'dip_bars', 'parallel_bars'],
  },
  {
    label: 'Rings setup',
    description: 'High-value setup for scalable pulling, support strength, and ring skills.',
    equipment: ['pull_up_bar', 'rings', 'resistance_band', 'training_mat'],
  },
  {
    label: 'Weighted strength',
    description: 'For athletes loading dips, pull-ups, push-ups, squats, and accessories.',
    equipment: ['pull_up_bar', 'dip_bars', 'dip_belt', 'weight_vest', 'weighted_backpack'],
  },
  {
    label: 'Travel kit',
    description: 'Portable options for sessions away from the usual training place.',
    equipment: ['resistance_band', 'suspension_trainer', 'jump_rope', 'training_mat'],
  },
]

export const trainingDayOptions: ChoiceOption[] = [
  { label: 'Mon', value: 'monday' },
  { label: 'Tue', value: 'tuesday' },
  { label: 'Wed', value: 'wednesday' },
  { label: 'Thu', value: 'thursday' },
  { label: 'Fri', value: 'friday' },
  { label: 'Sat', value: 'saturday' },
  { label: 'Sun', value: 'sunday' },
]

export const trainingLocationOptions: ChoiceOption[] = [
  { label: 'Home', value: 'home' },
  { label: 'Gym', value: 'gym' },
  { label: 'Park', value: 'park' },
  { label: 'Travel', value: 'travel' },
  { label: 'Other', value: 'other' },
]

export const trainingTimeOptions: ChoiceOption[] = [
  { label: 'Morning', value: 'morning' },
  { label: 'Midday', value: 'midday' },
  { label: 'Evening', value: 'evening' },
  { label: 'Flexible', value: 'flexible' },
]

export const progressionPaceOptions: ChoiceOption[] = [
  { label: 'Conservative', value: 'conservative', description: 'Small jumps with more time to adapt.' },
  { label: 'Balanced', value: 'balanced', description: 'Progress when your evidence is strong.' },
  { label: 'Ambitious', value: 'ambitious', description: 'Push faster while still respecting safety.' },
]

export const intensityPreferenceOptions: ChoiceOption[] = [
  { label: 'Auto', value: 'auto' },
  { label: 'Low', value: 'low' },
  { label: 'Moderate', value: 'moderate' },
  { label: 'High', value: 'high' },
]

export const effortTrackingOptions: ChoiceOption[] = [
  { label: 'Simple', value: 'simple' },
  { label: 'RIR', value: 'rir' },
  { label: 'RPE', value: 'rpe' },
  { label: 'Both', value: 'both' },
]

export const deloadPreferenceOptions: ChoiceOption[] = [
  { label: 'Auto', value: 'auto' },
  { label: 'Scheduled', value: 'scheduled' },
  { label: 'Manual', value: 'manual' },
]

export const sessionStructureOptions: ChoiceOption[] = [
  { label: 'Skill first', value: 'skill_first' },
  { label: 'Strength first', value: 'strength_first' },
  { label: 'Hypertrophy focus', value: 'hypertrophy_focus' },
  { label: 'Mobility finish', value: 'mobility_finish' },
  { label: 'Conditioning finish', value: 'conditioning_finish' },
  { label: 'Longer warm-up', value: 'longer_warmup' },
  { label: 'Unilateral work', value: 'unilateral_work' },
  { label: 'Isometrics', value: 'isometrics' },
  { label: 'Explosive work', value: 'explosive_work' },
]

export const limitationAreaOptions: ChoiceOption[] = [
  { label: 'Wrist', value: 'wrist' },
  { label: 'Elbow', value: 'elbow' },
  { label: 'Shoulder', value: 'shoulder' },
  { label: 'Neck', value: 'neck' },
  { label: 'Back', value: 'back' },
  { label: 'Hip', value: 'hip' },
  { label: 'Knee', value: 'knee' },
  { label: 'Ankle', value: 'ankle' },
  { label: 'General', value: 'general' },
  { label: 'Other', value: 'other' },
]

export const limitationSeverityOptions: ChoiceOption[] = [
  { label: 'Mild', value: 'mild' },
  { label: 'Moderate', value: 'moderate' },
  { label: 'Severe', value: 'severe' },
]

export const limitationStatusOptions: ChoiceOption[] = [
  { label: 'Active', value: 'active' },
  { label: 'Recurring', value: 'recurring' },
  { label: 'Past', value: 'past' },
]
