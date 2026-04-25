import type { ChoiceOption } from '../types'

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

export const equipmentOptions: ChoiceOption[] = [
  { label: 'Floor', value: 'floor' },
  { label: 'Wall', value: 'wall' },
  { label: 'Pull-up bar', value: 'pull_up_bar' },
  { label: 'Parallel bars', value: 'parallel_bars' },
  { label: 'Parallettes', value: 'parallettes' },
  { label: 'Rings', value: 'rings' },
  { label: 'Resistance band', value: 'resistance_band' },
  { label: 'Chair or box', value: 'chair_box' },
  { label: 'Weight vest', value: 'weight_vest' },
  { label: 'Dip belt', value: 'dip_belt' },
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
