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

export const unitSystemOptions: ChoiceOption[] = [
  { label: 'Metric', value: 'metric', description: 'Kilograms and centimeters.' },
  { label: 'Imperial', value: 'imperial', description: 'Pounds and familiar US units.' },
]

export const bodyweightUnitOptions: ChoiceOption[] = [
  { label: 'kg', value: 'kg' },
  { label: 'lb', value: 'lb' },
]

export const weightTrendOptions: ChoiceOption[] = [
  { label: 'Maintaining', value: 'maintaining', description: 'Bodyweight is roughly stable.' },
  { label: 'Gaining', value: 'gaining', description: 'Use when strength goals may have more recovery support.' },
  { label: 'Cutting', value: 'cutting', description: 'Use when high-force skills may need a slower ramp.' },
  { label: 'Unknown', value: 'unknown', description: 'Skip the modifier when you are not tracking it.' },
]

export const heightUnitOptions: ChoiceOption[] = [
  { label: 'cm', value: 'cm' },
  { label: 'in', value: 'in' },
]

export const priorSportOptions: ChoiceOption[] = [
  { label: 'None yet', value: 'none', description: 'Starting fresh is useful signal too.' },
  { label: 'Strength training', value: 'strength_training', description: 'Weights or structured strength work.' },
  { label: 'Gymnastics', value: 'gymnastics', description: 'Skill, shape, tumbling, or apparatus background.' },
  { label: 'Climbing', value: 'climbing', description: 'Grip, pulling, and body-tension background.' },
  { label: 'Martial arts', value: 'martial_arts', description: 'Body control, mobility, and conditioning.' },
  { label: 'Endurance sport', value: 'endurance_sport', description: 'Running, cycling, rowing, or aerobic base.' },
  { label: 'Team sport', value: 'team_sport', description: 'Field or court sport background.' },
  {
    label: 'Dance or mobility',
    value: 'dance_or_mobility',
    description: 'Flexibility, line, or body awareness background.',
  },
  { label: 'Other', value: 'other', description: 'Anything else that may affect starting point.' },
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

export const targetSkillOptions: ChoiceOption[] = [
  {
    label: 'Push-up',
    value: 'strict_push_up',
    description: 'Build clean pressing volume without sagging hips.',
  },
  {
    label: 'One-arm push-up',
    value: 'one_arm_push_up',
    description: 'Build unilateral pressing with anti-rotation control.',
  },
  {
    label: 'Pull-up',
    value: 'strict_pull_up',
    description: 'Build the pulling base toward clean full reps.',
  },
  {
    label: 'Weighted pull-up',
    value: 'weighted_pull_up',
    description: 'Treat pulling strength like a measurable lift.',
  },
  { label: 'Dip', value: 'strict_dip', description: 'Own support strength and controlled depth.' },
  { label: 'Ring dip', value: 'ring_dip', description: 'Build stable support and ring pressing control.' },
  { label: 'Weighted dip', value: 'weighted_dip', description: 'Progress dips with measured external load.' },
  { label: 'Muscle-up', value: 'muscle_up', description: 'Connect explosive pulling, transition, and dip strength.' },
  {
    label: 'Weighted muscle-up',
    value: 'weighted_muscle_up',
    description: 'Peak transition strength with load.',
  },
  { label: 'L-sit', value: 'l_sit', description: 'Train compression, straight-arm support, and midline control.' },
  { label: 'V-sit', value: 'v_sit', description: 'Extend compression and support strength beyond L-sit.' },
  { label: 'Handstand', value: 'handstand', description: 'Build balance, shoulder line, and overhead confidence.' },
  { label: 'Handstand push-up', value: 'handstand_push_up', description: 'Progress vertical pressing with control.' },
  {
    label: 'Press to handstand',
    value: 'press_to_handstand',
    description: 'Blend compression, balance, and straight-arm strength.',
  },
  { label: 'Front lever', value: 'front_lever', description: 'Develop straight-arm pulling and bodyline tension.' },
  { label: 'Back lever', value: 'back_lever', description: 'Build shoulder extension strength and lever control.' },
  {
    label: 'Planche',
    value: 'planche',
    description: 'Progress forward lean, scapular protraction, and push strength.',
  },
  { label: 'Pistol squat', value: 'pistol_squat', description: 'Improve single-leg strength, balance, and control.' },
  { label: 'Nordic curl', value: 'nordic_curl', description: 'Target hamstring strength with precise eccentrics.' },
  { label: 'One-arm pull-up', value: 'one_arm_pull_up', description: 'Build toward advanced unilateral pulling.' },
  { label: 'Human flag', value: 'human_flag', description: 'Combine side-body strength, pulling, and bracing.' },
]

export const baseFocusOptions: ChoiceOption[] = [
  { label: 'Push capacity', value: 'push_capacity', description: 'Clean reps and pressing volume.' },
  { label: 'Pull capacity', value: 'pull_capacity', description: 'Rows, hangs, pulls, and scapular control.' },
  { label: 'Dip support', value: 'dip_support', description: 'Support holds, lockout, and dip depth.' },
  { label: 'Row volume', value: 'row_volume', description: 'Fatigue-friendly pulling support.' },
  { label: 'Leg strength', value: 'leg_strength', description: 'Squat, single-leg, and posterior-chain base.' },
  { label: 'Core bodyline', value: 'core_bodyline', description: 'Hollow, arch, and trunk control.' },
  { label: 'Compression', value: 'compression', description: 'L-sit, hanging raise, and press prep.' },
  { label: 'Handstand line', value: 'handstand_line', description: 'Wrist, shoulder, and inversion tolerance.' },
  {
    label: 'Straight-arm tolerance',
    value: 'straight_arm_tolerance',
    description: 'Planche, lever, and ring tissue prep.',
  },
  {
    label: 'Mobility positions',
    value: 'mobility_positions',
    description: 'Positions that unlock safer progressions.',
  },
  { label: 'Weighted strength', value: 'weighted_strength', description: 'Measured added-load pull and dip work.' },
  {
    label: 'Conditioning base',
    value: 'conditioning_base',
    description: 'Work capacity without stealing skill quality.',
  },
]

export const mobilityCheckOptions: ChoiceOption[] = [
  { label: 'Wrist extension', value: 'wrist_extension', description: 'Planche, handstand, push-up comfort.' },
  { label: 'Shoulder flexion', value: 'shoulder_flexion', description: 'Handstand and overhead pressing line.' },
  { label: 'Shoulder extension', value: 'shoulder_extension', description: 'Dips, rings, and back lever tolerance.' },
  { label: 'Ankle dorsiflexion', value: 'ankle_dorsiflexion', description: 'Squat and pistol depth.' },
  { label: 'Pancake/compression', value: 'pancake_compression', description: 'L-sit, V-sit, and press work.' },
]

export const mobilityStatusOptions: ChoiceOption[] = [
  { label: 'Not tested', value: 'not_tested' },
  { label: 'Clear', value: 'clear' },
  { label: 'Limited', value: 'limited' },
  { label: 'Blocked', value: 'blocked' },
  { label: 'Painful', value: 'painful' },
]

export const weightedExperienceOptions: ChoiceOption[] = [
  { label: 'No weighted work yet', value: 'none' },
  { label: 'Curious', value: 'curious' },
  { label: 'Weighted reps', value: 'repetition_work' },
  { label: 'Strength cycles', value: 'strength_cycles' },
  { label: 'Competition style', value: 'competition_style' },
]

export const weightedMovementOptions: ChoiceOption[] = [
  { label: 'Weighted pull-up', value: 'weighted_pull_up' },
  { label: 'Weighted dip', value: 'weighted_dip' },
  { label: 'Weighted muscle-up', value: 'weighted_muscle_up' },
  { label: 'Weighted pistol', value: 'weighted_pistol' },
]

export const rowVariantOptions: ChoiceOption[] = [
  { label: 'Bodyweight row', value: 'bodyweight_row' },
  { label: 'Ring row', value: 'ring_row' },
  { label: 'Low bar row', value: 'low_bar_row' },
  { label: 'Suspension row', value: 'suspension_row' },
]

export const lowerBodyVariantOptions: ChoiceOption[] = [
  { label: 'Bodyweight squat', value: 'bodyweight_squat' },
  { label: 'Split squat', value: 'split_squat' },
  { label: 'Pistol progression', value: 'pistol_progression' },
  { label: 'Step-down', value: 'step_down' },
  { label: 'Barbell squat', value: 'barbell_squat' },
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
        description: 'Pulls, hangs, raises, muscle-up prep, and weighted pulling.',
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
