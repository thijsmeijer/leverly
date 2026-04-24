# Leverly Assumptions And Deferrals

This document records product assumptions and intentional deferrals for the first Leverly build. It keeps MVP scope narrow while leaving room for future expansion.

## MVP Scope Guardrails

Leverly starts as a responsive web app for calisthenics-first workout logging, progression tracking, and deterministic coaching recommendations.

The MVP must not implement these areas unless the product owner explicitly requests a scope change:

- Native mobile applications.
- Meal planning, calorie tracking, or nutrition coaching.
- Social feeds, follower graphs, public timelines, or community posting.
- Trainer marketplaces, coach discovery, or payment flows for coaching services.
- Computer-vision form analysis from photos or video.
- Wearable integrations.
- Paid subscriptions, billing, invoicing, or payment processing.
- Medical rehab programming, diagnosis, treatment plans, or clinician workflows.

## Future Expansion Rule

Future-facing code and documentation may leave extension points for these areas, but the first implementation should not build workflows, data models, UI, jobs, integrations, or dependencies that make them active product features.

When a future expansion is useful to mention, describe it as deferred and keep the current implementation focused on calisthenics training, structured workout logging, athlete-owned data, and rule-based progression recommendations.

## Current Assumptions

- One account represents one athlete.
- The first UI language is English.
- Leverly is not medical software.
- Pain and injury signals should make recommendations conservative.
- Workout logging must be efficient on phone-sized screens.
- Recommendations should be explainable and editable.
- AI-assisted features are optional and must not be required for core recommendations.
- Bodyweight-only training comes first; weighted calisthenics and resistance bands can be layered in through explicit stories.

## Open Owner Preference Questions

These questions should inform later personalization and product polish, but they do not block the foundation work.

- Which equipment should the first onboarding flow prioritize: pull-up bar, rings, parallettes, bands, dip bars, weights, or bodyweight-only training?
- Which goals should be first-class options: strength, skill progression, hypertrophy, conditioning, mobility, fat loss support, or a blend?
- Should progress media be part of a later release, and if so should it focus on private form reference, transformation history, or coach review?
- How central should AI feel in the product: hidden assistant, optional explanation layer, searchable knowledge layer, or visible coaching companion?
- Should the MVP assume solo athletes only, or should coach-managed athletes and shared programs influence early data boundaries?
- Should effort input favor RIR, RPE, simple difficulty labels, or a combined advanced mode?
- Should bodyweight be required for progression logic from the start, optional per athlete, or only required for weighted calisthenics and analytics?
- Should shared templates start as private reusable templates, curated defaults, import/export files, or a future public library?

Answers can update future assumptions, implementation stories, onboarding defaults, and recommendation settings.

## Intentional Deferrals

- Native app distribution is deferred until the web app proves the core logging and progression workflow.
- Nutrition and meal planning are deferred because they would shift the product away from training progression.
- Social and marketplace features are deferred because privacy, moderation, discovery, and monetization would materially expand the MVP.
- Computer-vision form analysis is deferred because the recommendation engine should first rely on explicit athlete inputs such as form quality, pain, readiness, reps, holds, tempo, load, and assistance.
- Wearable integrations are deferred until core session logging, readiness input, and trend analysis are stable.
- Billing is deferred until there is a clear paid product surface.
- Medical rehab is out of scope; Leverly can provide conservative training guidance but must not diagnose or prescribe treatment.
