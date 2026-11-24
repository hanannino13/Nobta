# apps/admin: NOBTA platform admin dashboard

This is the web dashboard for **Platform Admins** only. Tenant users (owners and employees) never sign in here.

**Status:** scaffolded in M1 with `create-vite@9.3.4 --template react-ts`: Vite 8, React 19.3.0 (pinned to match mobile, see ADR-013), TypeScript ~7.0.2, oxlint, and Vitest 5 with jsdom and React Testing Library. Commands: `dev`, `build`, `lint`, `typecheck`, `test`.

Plan:
- React + strict TypeScript + Vite, TanStack Query for server state, and a router to be chosen in M2 (moved from M1 on 2026-11-11).
- It talks only to the admin API surface `/api/admin/v1/*` through the `admin` guard. See ADR-005 in [ARCHITECTURE_DECISIONS.md](../../docs/architecture/ARCHITECTURE_DECISIONS.md).
- English first, with RTL-ready layout via the `dir` attribute and CSS logical properties.
- Tests use Vitest with React Testing Library. Playwright smoke tests come later.

Decision (M1): a custom React + TypeScript + Vite app. The `react-admin` framework isn't used.
