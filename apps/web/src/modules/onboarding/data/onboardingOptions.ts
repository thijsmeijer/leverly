import {
  compatibleSecondaryGoals,
  equipmentCategories,
  goalOptions,
  limitationAreaOptions,
  trainingDayOptions,
  trainingLocationOptions,
  trainingTimeOptions,
} from '@/modules/settings'

import type { ChoiceOption } from '../types'

export { compatibleSecondaryGoals, equipmentCategories, goalOptions, trainingDayOptions, trainingLocationOptions }

export const targetSkillOptions: ChoiceOption[] = [
  {
    label: 'Strict push-up',
    value: 'strict_push_up',
    description: 'Build clean pressing volume without sagging hips.',
  },
  {
    label: 'Strict pull-up',
    value: 'strict_pull_up',
    description: 'Move from rows or assisted reps toward full pulls.',
  },
  { label: 'Strict dip', value: 'strict_dip', description: 'Own support strength and controlled depth.' },
  { label: 'Muscle-up', value: 'muscle_up', description: 'Connect explosive pulling, transition, and dip strength.' },
  { label: 'L-sit', value: 'l_sit', description: 'Train compression, straight-arm support, and midline control.' },
  { label: 'Handstand', value: 'handstand', description: 'Build balance, shoulder line, and overhead confidence.' },
  { label: 'Handstand push-up', value: 'handstand_push_up', description: 'Progress vertical pressing with control.' },
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

export const pullUpProgressionOptions: ChoiceOption[] = [
  { label: 'Dead hang', value: 'dead_hang', description: 'Grip and shoulder tolerance.' },
  { label: 'Scapular pull', value: 'scapular_pull', description: 'Active shoulder depression.' },
  { label: 'Inverted row', value: 'inverted_row', description: 'Horizontal pulling base.' },
  { label: 'Band-assisted pull-up', value: 'band_assisted_pull_up', description: 'Full path with assistance.' },
  { label: 'Eccentric pull-up', value: 'eccentric_pull_up', description: 'Controlled lowering.' },
  { label: 'Strict pull-up', value: 'strict_pull_up', description: 'Clean unassisted reps.' },
  { label: 'Chest-to-bar pull-up', value: 'chest_to_bar_pull_up', description: 'Higher pull strength.' },
  { label: 'Weighted pull-up', value: 'weighted_pull_up', description: 'Added-load pulling.' },
]

export const squatProgressionOptions: ChoiceOption[] = [
  { label: 'Box squat', value: 'box_squat', description: 'Controlled depth target.' },
  { label: 'Air squat', value: 'air_squat', description: 'Bodyweight squat base.' },
  { label: 'Reverse lunge', value: 'reverse_lunge', description: 'Single-leg entry point.' },
  { label: 'Split squat', value: 'split_squat', description: 'Stable single-leg strength.' },
  { label: 'Assisted pistol', value: 'assisted_pistol', description: 'Pistol pattern with support.' },
  { label: 'Shrimp squat', value: 'shrimp_squat', description: 'Knee-dominant single-leg work.' },
  { label: 'Pistol squat', value: 'pistol_squat', description: 'Full single-leg squat.' },
  { label: 'Weighted pistol', value: 'weighted_pistol', description: 'Loaded single-leg strength.' },
]

export const skillStatusKeys = ['dip', 'l_sit', 'handstand', 'front_lever', 'planche'] as const

export const skillStatusLabels: Record<(typeof skillStatusKeys)[number], string> = {
  dip: 'Dip',
  front_lever: 'Front lever',
  handstand: 'Handstand',
  l_sit: 'L-sit',
  planche: 'Planche',
}

export const skillStatusOptions: ChoiceOption[] = [
  { label: 'Not started', value: 'not_started' },
  { label: 'Building base', value: 'building_base' },
  { label: 'Assisted', value: 'assisted' },
  { label: 'Partial range', value: 'partial_range' },
  { label: 'Single rep', value: 'single_rep' },
  { label: 'Multiple reps', value: 'multiple_reps' },
  { label: 'Short hold', value: 'short_hold' },
  { label: 'Solid hold', value: 'solid_hold' },
  { label: 'Advanced variation', value: 'advanced_variation' },
]

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
export const onboardingTrainingTimeOptions = trainingTimeOptions
