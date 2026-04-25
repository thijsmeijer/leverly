# Visual Design System

Leverly uses semantic UI tokens and a small shared component layer so screens stay calm, premium, responsive, and accessible.

## Tokens

Core colors are defined in `apps/web/src/style.css` as semantic Tailwind tokens:

- `surface-*` for page, panel, muted, elevated, overlay, and inverse surfaces.
- `ink-*` for primary, secondary, muted, and inverse text.
- `accent-*` for primary actions and secondary information.
- `status-*` for success, warning, and danger states.
- `radius-*` and `shadow-*` for card, control, and shell depth.

Light mode is the default. Dark mode is supported through system preference and explicit `data-theme="dark"` or `data-theme="light"` on the document root.

## Primitives

Shared primitives live under `apps/web/src/shared/ui`:

- `UiButton` for accessible primary, secondary, ghost, and inverse actions.
- `UiCard` for elevated, muted, inverse, and soft panels.
- `UiBadge` for neutral, success, warning, danger, info, and inverse status labels.
- `UiProgress` for accessible progress indicators.
- `UiSectionHeader` for consistent section headings, descriptions, and metrics.

Use primitives for repeated UI structure. Keep route components focused on layout and workflow composition.

## Accessibility

Interactive primitives preserve a minimum 44px touch target and use visible focus rings. Status color should be paired with text labels, not used as the only signal.
