# @nobta/api-types

Planned home of the TypeScript types for the NOBTA REST API, to be consumed by `apps/mobile` and `apps/admin`.

**Current state (M1):** an empty placeholder. It has no source files and no entry point, no app depends on it yet, and no types have been generated. OpenAPI generation is planned for M2 (ADR-020); it is not implemented.

**Source of truth (from M2):** the OpenAPI 3.1 spec produced by the backend. These types will be **generated**, not hand-written, so that the clients and the server can't drift apart. The generator (e.g. `openapi-typescript`) is chosen and added in M2 (ADR-020) once the backend exposes a spec. See [API_CONVENTIONS.md](../../docs/api/API_CONVENTIONS.md).

Rules:
- Never edit generated files by hand.
- Breaking changes to `/api/v1` aren't allowed. They go into a new version.
