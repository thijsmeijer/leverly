import type { RoutePlaceholderContent } from '@/app/router/routeMeta'

export const routePlaceholders = {
  today: {
    eyebrow: 'Session',
    title: "Today's training",
    description:
      'A focused logging surface will sit here with the next target, quick set actions, and readiness checks.',
    status: 'Ready for session setup',
    primaryAction: { label: 'Review workouts', to: { name: 'workouts' } },
    secondaryAction: { label: 'Open dashboard', to: { name: 'dashboard' } },
    metrics: [
      {
        label: 'Primary signal',
        value: 'Form',
        detail: 'Quality gates stay visible before overload.',
        tone: 'success',
      },
      { label: 'Logging speed', value: 'Fast', detail: 'Large controls for phone use during training.', tone: 'info' },
      {
        label: 'Safety input',
        value: 'Pain',
        detail: 'Pain and readiness remain part of the session flow.',
        tone: 'warning',
      },
    ],
    steps: [
      { label: 'Pick the current block', detail: 'Skill, strength, accessory, density, or conditioning.' },
      {
        label: 'Log the next set',
        detail: 'Capture reps, hold time, effort, form, and pain without leaving the session.',
      },
      {
        label: 'Close with evidence',
        detail: 'Session data will feed the recommendation engine after logging exists.',
      },
    ],
  },
  workouts: {
    eyebrow: 'Training log',
    title: 'Workout history',
    description: 'Completed and active sessions will be grouped by date, block type, and progression signal.',
    status: 'Ready for workout records',
    primaryAction: { label: 'Start today', to: { name: 'today' } },
    secondaryAction: { label: 'Review insights', to: { name: 'insights' } },
    metrics: [
      {
        label: 'Blocks',
        value: 'Structured',
        detail: 'Sessions keep skill, strength, tempo, and density work separate.',
        tone: 'info',
      },
      {
        label: 'Evidence',
        value: 'Set-level',
        detail: 'Reps, holds, effort, form, and pain belong to each logged set.',
        tone: 'success',
      },
      {
        label: 'Review',
        value: 'Weekly',
        detail: 'Training history will support weekly trend checks.',
        tone: 'neutral',
      },
    ],
    steps: [
      { label: 'Browse sessions', detail: 'See recent blocks and completion quality at a glance.' },
      { label: 'Open a workout', detail: 'Review set rows, notes, and recommendation-relevant signals.' },
      { label: 'Repeat useful work', detail: 'Carry forward a previous block when it still fits readiness.' },
    ],
  },
  workoutDetail: {
    eyebrow: 'Workout',
    title: 'Workout detail',
    description: 'The selected session will show block structure, set rows, notes, and recommendation inputs.',
    status: 'Ready for session detail',
    primaryAction: { label: 'Back to workouts', to: { name: 'workouts' } },
    secondaryAction: { label: 'Open today', to: { name: 'today' } },
    metrics: [
      {
        label: 'Rows',
        value: 'Sets',
        detail: 'Each set can carry performance and subjective feedback.',
        tone: 'success',
      },
      { label: 'Blocks', value: 'Clear', detail: 'Training work stays grouped by purpose and format.', tone: 'info' },
      {
        label: 'Signals',
        value: 'Readable',
        detail: 'The detail view will show what drove future suggestions.',
        tone: 'neutral',
      },
    ],
    steps: [
      {
        label: 'Inspect block order',
        detail: 'Skill work, strength work, accessories, and conditioning stay distinct.',
      },
      { label: 'Review set evidence', detail: 'Effort, form, pain, and completion data explain the session outcome.' },
      { label: 'Use it next time', detail: 'Repeat or adjust a session when the recommendation engine supports it.' },
    ],
  },
  programs: {
    eyebrow: 'Programming',
    title: 'Programs',
    description:
      'Training blocks will organize goals, weekly rhythm, and progression focus without hiding safety gates.',
    status: 'Ready for program structure',
    primaryAction: { label: 'Open progressions', to: { name: 'progressions' } },
    secondaryAction: { label: 'Start today', to: { name: 'today' } },
    metrics: [
      {
        label: 'Cycle',
        value: 'Weekly',
        detail: 'Programs will balance training days, recovery, and skill exposure.',
        tone: 'info',
      },
      {
        label: 'Goal',
        value: 'Focused',
        detail: 'Progressions stay attached to concrete calisthenics targets.',
        tone: 'success',
      },
      {
        label: 'Adjustments',
        value: 'Conservative',
        detail: 'Readiness and pain can hold or reduce planned work.',
        tone: 'warning',
      },
    ],
    steps: [
      { label: 'Choose a focus', detail: 'Pull, push, skill, legs, core, or mixed calisthenics goals.' },
      { label: 'Review the week', detail: 'Training days and recovery days should be clear before logging.' },
      { label: 'Adapt with evidence', detail: 'Program changes should follow recent performance and feedback.' },
    ],
  },
  programDetail: {
    eyebrow: 'Program',
    title: 'Program detail',
    description: 'The selected program will show weeks, sessions, target progressions, and current training phase.',
    status: 'Ready for program detail',
    primaryAction: { label: 'Back to programs', to: { name: 'programs' } },
    secondaryAction: { label: 'Open today', to: { name: 'today' } },
    metrics: [
      {
        label: 'Weeks',
        value: 'Phased',
        detail: 'Training focus can change across blocks without losing history.',
        tone: 'info',
      },
      {
        label: 'Targets',
        value: 'Specific',
        detail: 'Exercises and gates stay tied to progression families.',
        tone: 'success',
      },
      {
        label: 'Recovery',
        value: 'Visible',
        detail: 'Low readiness can change the session before overload.',
        tone: 'warning',
      },
    ],
    steps: [
      { label: 'Scan the week', detail: 'See the intended session order and recovery spacing.' },
      { label: 'Open a session', detail: 'Move from program structure into fast workout logging.' },
      { label: 'Review changes', detail: 'Recommendation history will explain any adjustment.' },
    ],
  },
  exercises: {
    eyebrow: 'Library',
    title: 'Exercise library',
    description: 'Exercises will be searchable by family, movement pattern, equipment, difficulty, and training kind.',
    status: 'Ready for exercise catalog',
    primaryAction: { label: 'Open progressions', to: { name: 'progressions' } },
    secondaryAction: { label: 'Review programs', to: { name: 'programs' } },
    metrics: [
      {
        label: 'Families',
        value: 'Skill-led',
        detail: 'Pull-up, dip, L-sit, planche, lever, and handstand paths stay grouped.',
        tone: 'success',
      },
      {
        label: 'Filters',
        value: 'Useful',
        detail: 'Equipment, level, and movement pattern can narrow the library.',
        tone: 'info',
      },
      {
        label: 'Technique',
        value: 'Explicit',
        detail: 'Form checks and common mistakes belong on detail screens.',
        tone: 'neutral',
      },
    ],
    steps: [
      { label: 'Search by target', detail: 'Find the right variation for a goal or movement pattern.' },
      { label: 'Check prerequisites', detail: 'Review technique and equipment before adding an exercise.' },
      { label: 'Connect to logging', detail: 'Exercise detail will feed workout rows and progression gates.' },
    ],
  },
  exerciseDetail: {
    eyebrow: 'Exercise',
    title: 'Exercise detail',
    description: 'The selected exercise will show instructions, equipment, progression links, and form checkpoints.',
    status: 'Ready for exercise detail',
    primaryAction: { label: 'Back to library', to: { name: 'exercises' } },
    secondaryAction: { label: 'Open progressions', to: { name: 'progressions' } },
    metrics: [
      {
        label: 'Mechanics',
        value: 'Specific',
        detail: 'Leverage, assistance, ROM, grip, tempo, and holds can be represented.',
        tone: 'success',
      },
      {
        label: 'Readiness',
        value: 'Relevant',
        detail: 'Exercise choice can respond to form and pain feedback.',
        tone: 'warning',
      },
      {
        label: 'Links',
        value: 'Progressive',
        detail: 'Each movement can connect to regressions and harder variations.',
        tone: 'info',
      },
    ],
    steps: [
      { label: 'Review setup', detail: 'Equipment, body position, and range targets should be obvious.' },
      { label: 'Check form', detail: 'Technique checkpoints prevent vague exercise notes.' },
      { label: 'Log or progress', detail: 'Use the exercise in a session or inspect the family path.' },
    ],
  },
  progressions: {
    eyebrow: 'Skill map',
    title: 'Progressions',
    description:
      'Progression families will show unlocked variations, gate evidence, regressions, and next sensible steps.',
    status: 'Ready for progression families',
    primaryAction: { label: 'Browse exercises', to: { name: 'exercises' } },
    secondaryAction: { label: 'Review insights', to: { name: 'insights' } },
    metrics: [
      {
        label: 'Gates',
        value: 'Evidence',
        detail: 'Unlocks depend on completion, form, pain, and readiness.',
        tone: 'success',
      },
      {
        label: 'Levers',
        value: 'One at a time',
        detail: 'Difficulty changes should avoid stacking too many jumps.',
        tone: 'warning',
      },
      {
        label: 'Families',
        value: 'Mapped',
        detail: 'Skill paths keep regressions and prerequisites visible.',
        tone: 'info',
      },
    ],
    steps: [
      { label: 'Pick a family', detail: 'Open push, pull, skill, core, leg, or mobility progressions.' },
      { label: 'Read the gate', detail: 'See what evidence is missing before a harder variation.' },
      { label: 'Choose the next step', detail: 'Progress, maintain, reduce, or regress based on the rules.' },
    ],
  },
  progressionDetail: {
    eyebrow: 'Progression',
    title: 'Progression family',
    description:
      'The selected family will show the current variation, gate checks, fallback paths, and unlock evidence.',
    status: 'Ready for family detail',
    primaryAction: { label: 'Back to progressions', to: { name: 'progressions' } },
    secondaryAction: { label: 'Browse exercises', to: { name: 'exercises' } },
    metrics: [
      {
        label: 'Current',
        value: 'Variation',
        detail: 'The athlete should always see the current working step.',
        tone: 'success',
      },
      {
        label: 'Gate',
        value: 'Visible',
        detail: 'Form, pain, RIR, and exposure requirements stay readable.',
        tone: 'warning',
      },
      {
        label: 'Fallback',
        value: 'Ready',
        detail: 'Regression and maintenance options remain close by.',
        tone: 'info',
      },
    ],
    steps: [
      { label: 'Review current evidence', detail: 'Recent sets and feedback show whether the gate is met.' },
      { label: 'Compare adjacent steps', detail: 'Harder variations and regressions should be easy to compare.' },
      { label: 'Apply conservatively', detail: 'Only one major difficulty lever should move by default.' },
    ],
  },
  insights: {
    eyebrow: 'Review',
    title: 'Insights',
    description:
      'Weekly trends will turn logged sessions into readable readiness, volume, form, and progression summaries.',
    status: 'Ready for analytics surfaces',
    primaryAction: { label: 'Open workouts', to: { name: 'workouts' } },
    secondaryAction: { label: 'Open dashboard', to: { name: 'dashboard' } },
    metrics: [
      {
        label: 'Trend',
        value: 'Readiness',
        detail: 'Subjective feedback should sit beside performance trends.',
        tone: 'info',
      },
      {
        label: 'Safety',
        value: 'Clear',
        detail: 'Pain and form flags need plain summaries before progression.',
        tone: 'warning',
      },
      {
        label: 'Output',
        value: 'Actionable',
        detail: 'The review should point to the next sensible session choice.',
        tone: 'success',
      },
    ],
    steps: [
      { label: 'Review the week', detail: 'Summaries compare recent work with target exposure.' },
      { label: 'Spot limiting signals', detail: 'Low readiness, form drops, and pain change the recommendation.' },
      { label: 'Choose the next action', detail: 'Progress, maintain, reduce, or deload with a clear reason.' },
    ],
  },
  settingsProfile: {
    eyebrow: 'Settings',
    title: 'Profile settings',
    description: 'Athlete context, units, goals, and training preferences will be edited here.',
    status: 'Ready for profile form',
    primaryAction: { label: 'Equipment', to: { name: 'settings-equipment' } },
    secondaryAction: { label: 'Export data', to: { name: 'settings-export' } },
    metrics: [
      {
        label: 'Context',
        value: 'Athlete',
        detail: 'Goals, units, training age, and schedule shape recommendations.',
        tone: 'success',
      },
      { label: 'Privacy', value: 'Owned', detail: 'Training data belongs to the signed-in athlete.', tone: 'info' },
      {
        label: 'Care',
        value: 'Conservative',
        detail: 'Limitations and pain notes should guide safe defaults.',
        tone: 'warning',
      },
    ],
    steps: [
      {
        label: 'Set training context',
        detail: 'Goals, experience, units, and availability make suggestions personal.',
      },
      { label: 'Review limitations', detail: 'Sensitive notes should be handled carefully and sparingly.' },
      { label: 'Save cleanly', detail: 'Validation and success states will be clear when the form exists.' },
    ],
  },
  settingsEquipment: {
    eyebrow: 'Settings',
    title: 'Equipment settings',
    description: 'Available training tools will shape exercise filters and program suggestions.',
    status: 'Ready for equipment selection',
    primaryAction: { label: 'Profile', to: { name: 'settings-profile' } },
    secondaryAction: { label: 'Export data', to: { name: 'settings-export' } },
    metrics: [
      {
        label: 'Core',
        value: 'Bodyweight',
        detail: 'Floor, wall, bar, rings, parallettes, and bands stay first-class.',
        tone: 'success',
      },
      {
        label: 'Weighted',
        value: 'Optional',
        detail: 'Vest and belt support can be added without changing the core flow.',
        tone: 'info',
      },
      {
        label: 'Filters',
        value: 'Practical',
        detail: 'Unavailable equipment should not appear as a default suggestion.',
        tone: 'warning',
      },
    ],
    steps: [
      { label: 'Select equipment', detail: 'Touch-friendly controls will make availability easy to update.' },
      { label: 'Filter exercise choices', detail: 'Library and program screens should respect what is available.' },
      { label: 'Keep it current', detail: 'Changing equipment should immediately affect future suggestions.' },
    ],
  },
  settingsExport: {
    eyebrow: 'Settings',
    title: 'Data export',
    description: 'Training history and profile data will be exportable from a clear, privacy-first screen.',
    status: 'Ready for export workflow',
    primaryAction: { label: 'Profile', to: { name: 'settings-profile' } },
    secondaryAction: { label: 'Open insights', to: { name: 'insights' } },
    metrics: [
      {
        label: 'Ownership',
        value: 'Clear',
        detail: 'Athletes should be able to take their training data with them.',
        tone: 'success',
      },
      {
        label: 'Scope',
        value: 'Readable',
        detail: 'Exports should explain what is included before download.',
        tone: 'info',
      },
      {
        label: 'Privacy',
        value: 'Careful',
        detail: 'Sensitive notes need deliberate handling in export flows.',
        tone: 'warning',
      },
    ],
    steps: [
      { label: 'Choose export scope', detail: 'Profile, workouts, readiness, and recommendations should be explicit.' },
      { label: 'Prepare securely', detail: 'Export generation should avoid leaking sensitive content into logs.' },
      { label: 'Download confidently', detail: 'Completion and failure states will be accessible and clear.' },
    ],
  },
} satisfies Record<string, RoutePlaceholderContent>
