import {
  compatibleSecondaryGoals,
  bodyweightUnitOptions,
  equipmentCategories,
  experienceLevelOptions,
  goalOptions,
  limitationAreaOptions,
  trainingDayOptions,
  trainingLocationOptions,
} from '@/modules/settings'

import type { ChoiceOption } from '../types'

export {
  bodyweightUnitOptions,
  compatibleSecondaryGoals,
  equipmentCategories,
  experienceLevelOptions,
  goalOptions,
  trainingDayOptions,
  trainingLocationOptions,
}

export const heightUnitOptions: ChoiceOption[] = [
  { label: 'cm', value: 'cm' },
  { label: 'in', value: 'in' },
]

export const priorSportOptions: ChoiceOption[] = [
  { label: 'None yet', value: 'none', description: 'Starting fresh is useful signal too.' },
  {
    label: 'Strength training',
    value: 'strength_training',
    description: 'Weights, machines, or structured strength work.',
  },
  { label: 'Gymnastics', value: 'gymnastics', description: 'Skill, shape, tumbling, or apparatus background.' },
  { label: 'Climbing', value: 'climbing', description: 'Grip, pulling, and body-tension background.' },
  { label: 'Martial arts', value: 'martial_arts', description: 'Body control, mobility, and conditioning.' },
  {
    label: 'Endurance sport',
    value: 'endurance_sport',
    description: 'Running, cycling, rowing, or similar aerobic base.',
  },
  { label: 'Team sport', value: 'team_sport', description: 'Field or court sport background.' },
  {
    label: 'Dance or mobility',
    value: 'dance_or_mobility',
    description: 'Flexibility, line, or body awareness background.',
  },
  { label: 'Other', value: 'other', description: 'Anything else that may affect starting point.' },
]

export const targetSkillOptions: ChoiceOption[] = [
  {
    label: 'Strict push-up',
    value: 'strict_push_up',
    description: 'Build clean pressing volume without sagging hips.',
  },
  {
    label: 'One-arm push-up',
    value: 'one_arm_push_up',
    description: 'Build unilateral pressing with strict anti-rotation control.',
  },
  {
    label: 'Strict pull-up',
    value: 'strict_pull_up',
    description: 'Build the pulling base toward clean full reps.',
  },
  {
    label: 'Weighted pull-up',
    value: 'weighted_pull_up',
    description: 'Treat pulling strength like a measurable lift.',
  },
  { label: 'Strict dip', value: 'strict_dip', description: 'Own support strength and controlled depth.' },
  { label: 'Ring dip', value: 'ring_dip', description: 'Build stable support and ring pressing control.' },
  { label: 'Weighted dip', value: 'weighted_dip', description: 'Progress strict dips with measured external load.' },
  { label: 'Muscle-up', value: 'muscle_up', description: 'Connect explosive pulling, transition, and dip strength.' },
  {
    label: 'Weighted muscle-up',
    value: 'weighted_muscle_up',
    description: 'Peak strict transition strength with load.',
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

export const skillStatusKeys = [
  'muscle_up',
  'l_sit',
  'handstand',
  'handstand_push_up',
  'front_lever',
  'back_lever',
  'planche',
  'pistol_squat',
  'one_arm_pull_up',
  'human_flag',
  'press_to_handstand',
] as const

export const skillStatusLabels: Record<(typeof skillStatusKeys)[number], string> = {
  back_lever: 'Back lever',
  front_lever: 'Front lever',
  handstand: 'Handstand',
  handstand_push_up: 'Handstand push-up',
  human_flag: 'Human flag',
  l_sit: 'L-sit',
  muscle_up: 'Muscle-up',
  one_arm_pull_up: 'One-arm pull-up',
  planche: 'Planche',
  pistol_squat: 'Pistol squat',
  press_to_handstand: 'Press to handstand',
}

export const skillStatusOptions: Record<(typeof skillStatusKeys)[number], ChoiceOption[]> = {
  back_lever: [
    { label: 'Not tested', value: 'not_tested' },
    { label: 'Skin-the-cat prep', value: 'skin_the_cat_prep' },
    { label: 'Tuck back lever', value: 'tuck_back_lever' },
    { label: 'Advanced tuck', value: 'advanced_tuck_back_lever' },
    { label: 'Straddle back lever', value: 'straddle_back_lever' },
    { label: 'Full back lever', value: 'full_back_lever' },
  ],
  front_lever: [
    { label: 'Not tested', value: 'not_tested' },
    { label: 'Tuck front lever', value: 'tuck_front_lever' },
    { label: 'Advanced tuck', value: 'advanced_tuck_front_lever' },
    { label: 'One-leg front lever', value: 'one_leg_front_lever' },
    { label: 'Half-lay front lever', value: 'half_lay_front_lever' },
    { label: 'Straddle front lever', value: 'straddle_front_lever' },
    { label: 'Full front lever', value: 'full_front_lever' },
  ],
  handstand: [
    { label: 'Not tested', value: 'not_tested' },
    { label: 'Wall plank', value: 'wall_plank' },
    { label: 'Chest-to-wall line', value: 'chest_to_wall_handstand' },
    { label: 'Wall shoulder taps', value: 'wall_handstand_shoulder_taps' },
    { label: 'Freestanding kick-up', value: 'freestanding_kick_up' },
    { label: 'Freestanding hold', value: 'freestanding_handstand' },
  ],
  handstand_push_up: [
    { label: 'Not tested', value: 'not_tested' },
    { label: 'Pike push-up', value: 'pike_push_up' },
    { label: 'Elevated pike push-up', value: 'elevated_pike_push_up' },
    { label: 'Wall negative', value: 'wall_hspu_negative' },
    { label: 'Partial wall HSPU', value: 'partial_wall_hspu' },
    { label: 'Full wall HSPU', value: 'full_wall_hspu' },
    { label: 'Deep HSPU', value: 'deep_handstand_push_up' },
    { label: 'Freestanding HSPU', value: 'freestanding_handstand_push_up' },
  ],
  human_flag: [
    { label: 'Not tested', value: 'not_tested' },
    { label: 'Side plank', value: 'side_plank' },
    { label: 'Vertical flag hold', value: 'vertical_flag_hold' },
    { label: 'Tuck flag', value: 'tuck_human_flag' },
    { label: 'Straddle flag', value: 'straddle_human_flag' },
    { label: 'Full human flag', value: 'full_human_flag' },
  ],
  l_sit: [
    { label: 'Not tested', value: 'not_tested' },
    { label: 'Tuck support', value: 'tuck_support' },
    { label: 'One-leg L-sit', value: 'one_leg_l_sit' },
    { label: 'Tuck L-sit', value: 'tuck_l_sit' },
    { label: 'Full L-sit', value: 'full_l_sit' },
    { label: 'V-sit prep', value: 'v_sit_prep' },
  ],
  muscle_up: [
    { label: 'Not tested', value: 'not_tested' },
    { label: 'Explosive pull-up', value: 'explosive_pull_up' },
    { label: 'Chest-to-bar pull-up', value: 'chest_to_bar_pull_up' },
    { label: 'High pull-up', value: 'high_pull_up' },
    { label: 'Band-assisted muscle-up', value: 'band_assisted_muscle_up' },
    { label: 'Negative muscle-up', value: 'negative_muscle_up' },
    { label: 'Strict muscle-up', value: 'strict_muscle_up' },
  ],
  one_arm_pull_up: [
    { label: 'Not tested', value: 'not_tested' },
    { label: 'Archer pull-up', value: 'archer_pull_up' },
    { label: 'Typewriter pull-up', value: 'typewriter_pull_up' },
    { label: 'Assisted one-arm pull-up', value: 'assisted_one_arm_pull_up' },
    { label: 'One-arm negative', value: 'one_arm_pull_up_negative' },
    { label: 'Strict one-arm pull-up', value: 'strict_one_arm_pull_up' },
  ],
  pistol_squat: [
    { label: 'Not tested', value: 'not_tested' },
    { label: 'Split squat', value: 'split_squat' },
    { label: 'Box pistol', value: 'box_pistol' },
    { label: 'Assisted pistol', value: 'assisted_pistol' },
    { label: 'Pistol negative', value: 'pistol_negative' },
    { label: 'Full pistol squat', value: 'full_pistol_squat' },
  ],
  planche: [
    { label: 'Not tested', value: 'not_tested' },
    { label: 'Planche lean', value: 'planche_lean' },
    { label: 'Frog stand', value: 'frog_stand' },
    { label: 'Tuck planche', value: 'tuck_planche' },
    { label: 'Advanced tuck planche', value: 'advanced_tuck_planche' },
    { label: 'Straddle planche', value: 'straddle_planche' },
    { label: 'Full planche', value: 'full_planche' },
  ],
  press_to_handstand: [
    { label: 'Not tested', value: 'not_tested' },
    { label: 'Compression lift', value: 'compression_lift' },
    { label: 'Elevated press lean', value: 'elevated_press_lean' },
    { label: 'Wall press negative', value: 'wall_press_negative' },
    { label: 'Straddle press negative', value: 'straddle_press_negative' },
    { label: 'Freestanding press', value: 'freestanding_press_to_handstand' },
  ],
}

export const skillStatusMeasurements: Record<
  (typeof skillStatusKeys)[number],
  { readonly hold?: string; readonly reps?: string }
> = {
  back_lever: { hold: 'Best hold' },
  front_lever: { hold: 'Best hold' },
  handstand: { hold: 'Best hold' },
  handstand_push_up: { reps: 'Best reps' },
  human_flag: { hold: 'Best hold' },
  l_sit: { hold: 'Best hold' },
  muscle_up: { reps: 'Best reps' },
  one_arm_pull_up: { reps: 'Best reps' },
  pistol_squat: { reps: 'Best reps' },
  planche: { hold: 'Best hold' },
  press_to_handstand: { reps: 'Best reps' },
}

export const starterPlanOptions: ChoiceOption[] = [
  {
    label: 'Full-body 3 day',
    value: 'full_body_3_day',
    description: 'Three balanced sessions with skill practice before strength work.',
    meta: 'Best first plan',
  },
  {
    label: 'Upper/lower 4 day',
    value: 'upper_lower_4_day',
    description: 'More weekly practice with clearer recovery between upper and lower sessions.',
    meta: 'More structure',
  },
  {
    label: 'Push/pull/legs',
    value: 'push_pull_legs_3_to_6_day',
    description: 'Flexible split for athletes who like focused training days.',
    meta: 'Flexible volume',
  },
  {
    label: 'Skill + strength split',
    value: 'skill_strength_split',
    description: 'Skill-first sessions supported by targeted strength accessories.',
    meta: 'Skill focused',
  },
  {
    label: 'Short maintenance',
    value: 'short_maintenance',
    description: 'Compact sessions for busy weeks while keeping progress alive.',
    meta: 'Low friction',
  },
]

export const readinessOptions: ChoiceOption[] = [
  { label: 'Low', value: '1', description: 'Keep work easy and technique-focused.' },
  { label: 'Below normal', value: '2', description: 'Use conservative progressions.' },
  { label: 'Normal', value: '3', description: 'Train as planned.' },
  { label: 'Strong', value: '4', description: 'Ready for productive work.' },
  { label: 'Excellent', value: '5', description: 'High-confidence training day.' },
]

export const sorenessOptions: ChoiceOption[] = [
  { label: 'None', value: '1' },
  { label: 'Light', value: '2' },
  { label: 'Moderate', value: '3' },
  { label: 'High', value: '4' },
  { label: 'Very high', value: '5' },
]

export const painOptions: ChoiceOption[] = Array.from({ length: 11 }, (_, value) => ({
  label: String(value),
  value: String(value),
  description: value === 0 ? 'No pain' : value >= 4 ? 'Requires conservative planning' : undefined,
}))

export const painAreaOptions = limitationAreaOptions

export const mobilityCheckOptions: ChoiceOption[] = [
  { label: 'Wrist extension', value: 'wrist_extension', description: 'Planche, handstand, and push-up loading.' },
  { label: 'Shoulder flexion', value: 'shoulder_flexion', description: 'Handstand line and overhead pressing.' },
  { label: 'Shoulder extension', value: 'shoulder_extension', description: 'Dips, rings, and back lever tolerance.' },
  { label: 'Ankle dorsiflexion', value: 'ankle_dorsiflexion', description: 'Squat depth and single-leg progressions.' },
  { label: 'Pancake/compression', value: 'pancake_compression', description: 'L-sit, V-sit, and press preparation.' },
]

export const mobilityTestInstructions: Record<string, { clear: string; test: string }> = {
  ankle_dorsiflexion: {
    clear:
      'Clear means your knee reaches the wall on both sides without the heel lifting, the arch collapsing, or ankle pain.',
    test: 'Try a knee-to-wall test with toes roughly 8 to 10 cm from the wall. Keep the heel down and drive the knee over the toes.',
  },
  pancake_compression: {
    clear:
      'Clear means you can hinge from the hips and create a strong forward tilt without cramps, sharp hamstring pain, or low-back strain.',
    test: 'Sit in a straddle or pike with knees straight. Reach forward, then try a small straight-leg lift or active compression hold.',
  },
  shoulder_extension: {
    clear:
      'Clear means the arms move behind the torso without front-shoulder pinching, elbow bend, or a forced chest flare.',
    test: 'Stand tall and reach straight arms behind you, or place hands on a box behind your hips and gently load the position.',
  },
  shoulder_flexion: {
    clear:
      'Clear means your arms reach close to your ears without bending the elbows, flaring the ribs, shrugging hard, or causing pain.',
    test: 'Stand with ribs down and raise straight arms overhead. A wall test is useful if you can keep the low back neutral.',
  },
  wrist_extension: {
    clear:
      'Clear means you can load the position without palm lift, sharp wrist pain, or a strong need to turn the hands out.',
    test: 'Place the palm flat on the floor or a table with the elbow straight, then gently shift the shoulder past the wrist.',
  },
}

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
