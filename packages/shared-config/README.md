# @nobta/shared-config

Shared tooling presets for every TypeScript project in the monorepo.

- `tsconfig/base.json` is the strict compiler baseline. Apps extend it and add their own platform settings (`expo/tsconfig.base` for mobile, the Vite/DOM settings for admin).

```jsonc
// apps/admin/tsconfig.app.json and apps/admin/tsconfig.node.json (M1)
{ "extends": "@nobta/shared-config/tsconfig/base.json", "compilerOptions": { /* app-specific */ } }
```

In admin, `apps/admin/tsconfig.json` does not extend the base: it only lists `tsconfig.app.json` and `tsconfig.node.json` as project references, and those two files extend it. Mobile's `apps/mobile/tsconfig.json` extends `["expo/tsconfig.base", "@nobta/shared-config/tsconfig/base.json"]`.

Shared ESLint and Prettier presets are added in M2 (moved from M1 on 2026-11-11), so that each dependency gets installed for a real consumer. Until then each app keeps its own lint config (mobile: `eslint-config-expo`; admin: `oxlint`), and Prettier is configured at the repo root.

Note: `exactOptionalPropertyTypes` is deliberately strict. If a framework's types prove incompatible during M1 scaffolding, relax it **in that app only** and record why.
