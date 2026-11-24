# Dependency Audit: M1 (2026-11-08, updated 2026-11-11, re-verified 2026-11-17)

Scope: npm workspaces (`apps/mobile`, `apps/admin`, `packages/*`) and the Composer backend.

**Status: ACCEPTED TEMPORARILY (2026-11-11).** See "Decision" below.

## Summary

npm's counts are **package entries** (packages npm marks vulnerable), not advisories. All entries trace back to the same **three advisories**.

| Audit | Command | Exit | Result (2026-11-11) |
|---|---|---|---|
| npm (all) | `npm audit` | 1 | 29 entries: 28 moderate, 1 low, 0 high, 0 critical (was 31 on 2026-11-08, see below) |
| npm ("production") | `npm audit --omit=dev` | 1 | 22 entries: 21 moderate, 1 low, 0 high, 0 critical (unchanged) |
| npm high/critical gate | `npm audit --omit=dev --audit-level=high` | 0 | No high or critical findings (passes) |
| Composer (all) | `composer audit` | 0 | No advisories |
| Composer (no dev) | `composer audit --locked --no-dev` | 0 | No advisories |

All 22 npm "production" findings trace back to **three advisories**:

| Advisory | Severity | Affected range | Installed | Patched |
|---|---|---|---|---|
| [GHSA-3g3m-2fjr-6pw4](https://github.com/advisories/GHSA-3g3m-2fjr-6pw4): glob AbortSignal cancellation bypass allows post-timeout filesystem traversal | moderate | `glob` >=9.0.0 <=14.0.1 | 13.0.6; 10.5.0 (nested copies) | 14.0.2+ |
| [GHSA-g3hv-75wg-m249](https://github.com/advisories/GHSA-g3hv-75wg-m249): minimatch DoS via globparts amplification | moderate | `minimatch` >=7.0.0 <11.0.1 | 10.2.6; 9.0.9 (nested copies) | 11.0.1+ |
| [GHSA-gcqx-w48m-w2h4](https://github.com/advisories/GHSA-gcqx-w48m-w2h4): deepmerge `mergeObject()` prototype poisoning | low | `deepmerge` >=4.2.0 <=4.3.1 | 4.3.1 | none published (4.3.1 is the latest) |

The other 19 entries are packages that npm flags only because they depend on one of these three.

Copies in the lockfile that are **outside** the affected ranges, and so not affected: `glob@7.2.3` (×1, via `test-exclude@6`) and `minimatch@3.1.5` (×6, via ESLint plugins and `test-exclude@6`). Installed affected copies: `glob@13.0.6` ×1, `glob@10.5.0` ×7, `minimatch@10.2.6` ×1, `minimatch@9.0.9` ×7, `deepmerge@4.3.1` ×1.

Re-verified 2026-11-08 against an unchanged `package-lock.json` (last modified 2026-11-05): totals and advisories are identical.

Re-verified 2026-11-11, `package-lock.json` still unchanged (last modified 2026-11-05): the three advisories, the installed versions and the 22 "production" entries are identical. The full-audit total dropped from 31 to 29 because npm no longer flags `eslint-config-expo` and `eslint-plugin-expo`; this comes from registry metadata, not from a dependency change. The vulnerable install instances total 62 across the 29 entries.

**Re-verified 2026-11-17** (read-only; `package-lock.json` still unchanged since 2026-11-05; no fix applied). The tables above are kept as the 2026-11-11 historical record. Current results:

| Audit | Command | Exit | Result (2026-11-17) |
|---|---|---|---|
| npm (all) | `npm audit` | 1 | 28 entries, all moderate; 0 low, 0 high, 0 critical |
| npm ("production") | `npm audit --omit=dev` | 1 | 21 entries, all moderate; 0 low, 0 high, 0 critical |
| npm high/critical gate | `npm audit --omit=dev --audit-level=high` | 0 | No high or critical findings (passes) |
| Composer (all, from lock) | `composer audit --locked` | 0 | No advisories |

Only **two advisories** are reported now: GHSA-3g3m-2fjr-6pw4 (`glob`) and GHSA-g3hv-75wg-m249 (`minimatch`), both moderate. GHSA-gcqx-w48m-w2h4 (`deepmerge`, low) is no longer reported, although `deepmerge@4.3.1` is still in the lockfile. The cause was not determined. The installed affected copies listed above are unchanged. The owner acknowledged these figures at the M1 sign-off review on 2026-11-18 (decision D4); the acceptance below stays **ACCEPTED TEMPORARILY**.

## Which workspace introduces them

| Affected package | Introduced by | Workspace | Declared as |
|---|---|---|---|
| `glob@13.0.6` | `@expo/cli`, `@expo/fingerprint` (via `expo`) | `@nobta/mobile` | `dependencies` → `expo` |
| `glob@13.0.6`, `glob@10.5.0`, `minimatch@9/10`, `deepmerge@4.3.1` | `jest`, `jest-config`, `@jest/*`, `test-exclude` (via `jest-expo`) | `@nobta/mobile` | `devDependencies`, but see below |
| `minimatch@10.2.6` | `@typescript-eslint/typescript-estree` (via `eslint-config-expo`) | `@nobta/mobile` | `devDependencies` |
| (none) | — | `@nobta/admin` | No affected package in its dependency chain |

**Why npm counts Jest as "production".** `npm explain jest` shows this chain:
`expo` (dependency) → `@expo/cli` → optional peer `expo-router` → optional peer `@testing-library/react-native` → optional peer `jest`.
npm classifies installed optional peers of production packages as non-dev (`devOptional`). So `--omit=dev` keeps the Jest tree even though only tests import it. **That's a quirk of npm's classification, and we don't treat it as proof that nothing ships.** We checked the built bundles directly, as described next.

## Real exposure

| Surface | Contains affected code? | Evidence |
|---|---|---|
| Mobile app (shipped JS bundle) | **No** | `npx expo export --platform android` produced a 2.48 MB Hermes bundle. Strings `minimatch`, `GLOBSTAR`, `deepmerge`, `jest-config`, `@expo/cli` and `@expo/fingerprint` are **absent**. Control strings (`NOBTA`, `SafeAreaView`, `react-native-screens`) are present, which confirms the search works on Hermes bytecode. |
| Admin dashboard (shipped JS bundle) | **No** | `vite build` output does not contain `minimatch`, `GLOBSTAR` or `deepmerge`. The admin app has no affected packages in its tree. |
| Developer machines and CI runners | **Yes** | `@expo/cli` (Metro dev server, `prebuild`, `export`), `@expo/fingerprint` and Jest run here. |
| EAS cloud build workers | **Yes (build time)** | `@expo/cli` and `@expo/fingerprint` run during builds. |
| Backend (Laravel) | No | No npm runtime. Composer audit is clean. |

**Exploitability in our usage:** each advisory needs attacker-controlled input to the library: glob patterns or traversal using an `AbortSignal` timeout (glob), attacker-supplied glob patterns (minimatch), or attacker-supplied objects merged by deepmerge. In our tooling the patterns and configs come from the repository and the Expo/Jest/ESLint configs, which developers control. We found no path for end-user input to reach these libraries; this lowers the practical risk but does not make it zero (for example, a malicious change to repository config could still reach them).

Reachability evidence (2026-11-11, installed sources, read-only):
- **glob:** the only caller found that passes an `AbortSignal` is `@expo/cli`'s `anyMatchAsync` helper (`build/src/utils/glob.js:36`). No caller of that helper was found in `@expo/cli` or in 37 other Expo packages searched (not all of `node_modules`). The Jest callers do not pass a signal.
- **minimatch:** called by `@expo/fingerprint` (ignore paths) and `@typescript-eslint/typescript-estree` (`allowDefaultProject` patterns); both take patterns from project configuration.
- **deepmerge:** `jest-config` merges Jest's defaults with our own Jest configuration.

## Fix assessment

| Option | Result |
|---|---|
| `npm audit fix` (non-breaking) | Not applicable to the advisories. No patched versions exist inside the ranges the parent packages declare (`glob ^13` / `^10.4.1`, `minimatch ^10.2.2` / `^9.0.4`). `deepmerge` has no patched release at all. *Update 2026-11-11:* npm now marks several entries as fixable in range, but the registry confirms only one improvement: `@expo/cli` 58.1.20 (allowed by `^58.1.19`) no longer depends on `glob`/`minimatch`. It does not clear any advisory (`glob` stays via Jest; `minimatch` stays via `@expo/fingerprint` 0.21.10 and typescript-eslint 8.72.2). The other "in range" claims could not be verified. Not applied. |
| npm's suggested fixes | `expo@52.0.49` and `jest@29.7.0`, both **breaking downgrades** that are incompatible with Expo SDK 58. Rejected. |
| `npm audit fix --force` | Not used (as required). |
| npm `overrides` forcing `glob@14` / `minimatch@11` | Rejected. It forces majors that the parents (`@expo/cli`, Jest) don't declare support for, which conflicts with the "no incompatible versions" rule. |

## Decision

**ACCEPTED TEMPORARILY (approved 2026-11-11).** The current `glob`, `minimatch` and `deepmerge` advisories are accepted temporarily.

- Basis: `npm audit --omit=dev --audit-level=high` finds **no high or critical** vulnerabilities (exit 0). The detailed "production" audit reports 22 entries (21 moderate, 1 low) tied to the three advisories above.
- **Acceptance does not mean the findings are gone or that the risk is zero.** The advisories remain present in development and build tooling.
- Conditions:
  - no `npm audit fix` and no `npm audit fix --force`; no `overrides` forcing unsupported majors; no dependency, package-file or lockfile changes as part of this decision;
  - re-review on every Expo patch update, at every milestone, and before any production release;
  - re-evaluate the acceptance immediately if any high or critical runtime vulnerability appears.
- Enforcement: CI runs `npm audit --omit=dev --audit-level=high` in the JS job and fails on any high or critical finding (added 2026-11-11; not yet run on GitHub).

## Mitigations and follow-up

1. These packages aren't in the shipped mobile or admin bundles (verified above). Re-verify after dependency changes.
2. Inputs to these tools stay developer-controlled. Don't feed user-supplied patterns or objects to build tooling.
3. Pick up fixes through the supported path: `npx expo install --fix` on each Expo SDK patch release, then `npx expo-doctor`. Re-run `npm audit --omit=dev`.
4. CI gate (added 2026-11-11, not yet run on GitHub): `npm audit --omit=dev --audit-level=high` as a blocking step in the JS job, so new high or critical advisories fail the build while these known moderate/low tooling findings don't.
5. Re-review at the start of each milestone and before any production release.
