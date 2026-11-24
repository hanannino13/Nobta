# M1 Final Sign-off: Gap Assessment (2026-11-18)

**Type:** read-only assessment for decision preparation, kept as a historical record (owner decision D8). It changes no status: M1 Final Sign-off stays **PENDING**, M2 stays **not authorized**, and every U-item, ADR and milestone status in [ROADMAP.md](ROADMAP.md) and [ARCHITECTURE_DECISIONS.md](ARCHITECTURE_DECISIONS.md) is unchanged.

**Revision:** corrected on 2026-11-18 after the owner's review of this report:
- review issues E1–E10;
- Batch 1 items that had been left out of the register;
- the owner decisions D1–D9;
- the authorized documentation-only remediation (D2).

**Evidence update (2026-11-19):** A1 is closed by a separately authorized full Pest run on MySQL `nobta_testing`. It is recorded in §15, and the affected passages carry a dated "Post-assessment (2026-11-19)" note next to their original text. The owner-authorized C19 documentation remediation of the same day is recorded in §16, with "Post-remediation (2026-11-19)" notes.

Unless a passage is marked as post-review or post-assessment, the assessed statuses below are those of the original assessment. §14 and §15 record what changed afterwards.

**Verdict: NOT READY FOR M1 FINAL SIGN-OFF.** See §11.

---

## 1. Executive summary

The 130-file inventory review is complete (130/130, Batches 1–14; Batch 14 ACCEPTED with no findings). Everything that can be verified locally without a database or GitHub has been verified:

- the backend and both apps are scaffolded at the versions in ADR-013;
- the backend hardening of ADR-021 is in place;
- the test-database guard has been strengthened and tested without a database;
- the M1 Android development build ran on the emulator;
- a secret scan of all 130 files found no real secrets.

**No Critical or High *defect* is open.** F1 is a different kind of item: a **process blocker** (CI has never run, and the first commit and push are still to come). It is not a code defect.

Sign-off is blocked by **missing execution evidence and pending process steps**, not by known code defects:

1. **GitHub Actions has never run (F1).** The repository has 0 commits and no remote. This is ROADMAP U15, and M1's exit criteria require lint, typecheck and tests to pass in CI.
2. **The full backend test suite has not run since 2026-11-11.**
   - The last full Pest run (58/58) predates the 2026-11-12 changes: the F5 guard rewrite, B2-M1 and B2-L1.
   - Since then only database-free subsets have run (at most 70 tests in one run). The MySQL-backed tests have not run.
   - *Post-assessment (2026-11-19):* **resolved.** The full current suite passed on MySQL `nobta_testing`: 94 tests, 165 assertions, exit 0 (A1 closed, §15). This was a local run, not CI.
3. **U15 asks for a *human* review of the untracked files.**
   - The 130-file review was performed by an AI assistant at the owner's request, as preparation material for the owner's review.
   - Owner decision D1 accepts it as detailed inventory review evidence.
   - The owner's own final check of the exact staged file list and the secret-scan summary is still required before the first commit (A2).

§4.2 lists 31 Low-or-above findings and process items: 30 open plus 1 accepted temporarily (B2-L2). **Of these, only F1 blocks M1.** The rest are mostly stale documentation and do not block sign-off on their own. Together they left the milestone rule "docs updated" (ROADMAP line 3) not fully met. The owner chose to fix the listed documentation findings (D2); §14 shows the result.

## 2. Verified baseline and integrity (as assessed)

Checked read-only at the start and end of the original assessment:

| Check | Result |
|---|---|
| Repository root / branch | `D:/hanan_projects/NOBTA`, `main` |
| Git | 15 untracked entries, 0 staged, 0 commits, 0 remotes (unchanged since Batch 1) |
| Inventory | 130 files; SHA-256 of all 130 identical to the end of Batch 14 |
| Files outside the inventory | none before this report (untracked list, `--exclude-standard`) |
| Review records | 130 reviewed, 0 duplicates, 0 missing, 0 extra |

This report adds one file, so the untracked inventory is now **131 files**, and the report must be included in the pre-commit review (§10). The authorized documentation remediation (D2) later changed 8 of the 130 reviewed files. Their before/after hashes are in §14.

## 3. Review completeness (130/130)

| Batch | Date | Scope (files) | Recorded outcome |
|---|---|---|---|
| Pre-review | 2026-11-12 | All 130, high-level | "NOT READY"; findings F1–F15 |
| 1 | 2026-11-12 | CI and backend safety (10) | Explanation prepared for the owner's human review; no finding IDs issued; owner questions Q1–Q9 and concerns raised (§4.5) |
| 2–3 | 2026-11-12 – 2026-11-15 | Backend config and app code | Findings B2-*, B3-* |
| 4–6 | 2026-11-15 | Backend tests, tooling, remaining backend files | Findings B4-*–B6-* |
| 7–8 | 2026-11-17 | Root files; shared packages and admin config | Findings B7-*, B8-* |
| 9 | 2026-11-17 | Admin app + infrastructure | ACCEPTED WITH FINDINGS |
| 10 | 2026-11-17 | Mobile app source and config | ACCEPTED WITH FINDINGS |
| 11 | 2026-11-17 | Mobile assets, branding, mobile `.gitignore` | ACCEPTED WITH FINDINGS |
| 12 | 2026-11-17 | Mobile tooling + 6 core docs | ACCEPTED WITH FINDINGS |
| 13 | 2026-11-17 | ADRs, ROADMAP, 8 backend `.gitignore` | ACCEPTED WITH FINDINGS |
| 14 | 2026-11-17 | Last 4 backend `.gitignore` | **ACCEPTED, no findings. Inventory review complete: 130/130** |

From Batch 9 on, each batch report ended with the reviewer's recommended decision. Owner acceptance is not recorded in repository history. It **is** recorded in the owner's review prompts, which stated, for example, "Batch 14: ACCEPTED" and "Previous batches: accepted".

Sources: this session's batch reports, scratchpad inventories `batch4`–`batch14`, and re-verification of each claimed fix against the current files.

## 4. Consolidated findings register

**Status key:**
- **Fixed (verified 2026-11-18):** the fix was re-checked in the current file.
- **Fixed 2026-11-18 (D2):** fixed by the authorized documentation remediation after the review (§14).
- **Open:** not changed.
- **Accepted temp.:** an owner-approved temporary deviation.
- **Deferred:** scheduled for a later milestone in the original finding.
- **Decision:** needs an owner decision.

"M1?" says whether the item blocks M1 sign-off. Duplicates and overlaps are **cross-referenced, not merged**: every original ID is kept.

### 4.1 Fixed before this assessment (re-verified 2026-11-18)

| ID | Sev. | Area | Fix verified in current file |
|---|---|---|---|
| F2 | Medium | `README.md:23`, `apps/mobile/README.md:11` | `npm run android` marked blocked on Windows (U16) |
| F3 | Medium | `apps/mobile/README.md:17` | Says linked to `@hanannino13/nobta` |
| F4 | Low | `backend/tests/Pest.php:7` | Comment now says `DatabaseTransactions` |
| F5 | Low | `TestDatabaseGuard.php:36` | Host allowlist `LOCAL_HOSTS` (+ read/write, `DB_URL`); 46 unit + 7 wiring tests passed DB-free 2026-11-12 |
| F6 | Low | `apps/mobile/AGENTS.md:20` | `npm run doctor` (pinned) |
| F8 | Low | `ROADMAP.md:34` (U11) | Clarified: EAS dev build installed locally |
| B2-M1 | Medium | `routes/web.php:9`, `config/sanctum.php:95` | `/` without `web` group; `'routes' => false`; ExampleTest 9/9 DB-free 2026-11-12 |
| B2-L1 | Low | `config/auth.php:48-51` | `sanctum` guard `provider => users`. A database-backed rejection test is still missing (ADR-021, last bullet) |
| B6-L1 | Low | `backend/README.md:3` | Surfaces marked planned |
| B7-L1 | Low | `.prettierignore`, `backend/.gitignore` | 0 CR bytes in both |
| B7-L4 | Low | `README.md:9,10,54` | `/api/v1` and OpenAPI marked planned (M2) |

### 4.2 Low-or-above findings and process items (31 = 30 open + 1 accepted temporarily, as assessed)

Every column except the last is the **original assessment**, unchanged: status, risk, action, milestone and owner flags as assessed on 2026-11-18, before the review and the D2 remediation. The last column records what happened **after** the review (D2 remediation, owner decisions D3/D4/D9) and the duplicate or related IDs. No underlying status or owner decision is changed by this layout.

| ID | Sev. | File / area | Status (as assessed) | Risk / impact | Action | Earliest milestone | M1? | Owner? | Post-review (2026-11-18) |
|---|---|---|---|---|---|---|---|---|---|
| F1 | Blocker (process) | ROADMAP:8,38,49 | Open, partly addressed (review 130/130 done) | M1 exit criteria need CI | Run CI on GitHub (§6) | M1 | **Yes** | Yes | Open (unchanged) |
| F7 | Low | `ci.yml` jobs | Open (0 `timeout-minutes`) | A hung job runs up to 6 h | Add job timeouts | Before first CI run (optional) | No | No | Open; CI change not authorized yet |
| B2-L2 | Low | `routes/api.php` `/api/user` | Accepted temp. (ADR-021 D7) | Raw model, internal id | Replace with `/api/v1/me` | M2 | No | No | Accepted temp. (unchanged); the one non-open item in this table |
| B2-L3 | Low | `AppServiceProvider` | Open | No Eloquent strictness | Enable outside production | M2 start | No | No | Open |
| B2-L4 | Low | `tests/Arch/ArchTest.php:14-16` | Open | Controller arch test is vacuous | Real arch rules with modules | M2 | No | No | Open; related: B13-L4 |
| B3-L1 | Low | `DemoSeeder.php:36-39` | Open | Seeder not re-runnable | `firstOrCreate` | M2/M3 | No | No | Open |
| B3-L2 | Low | `config/queue.php` `after_commit` | Open (mitigated today) | Jobs for rolled-back bookings | `after_commit` / `ShouldQueueAfterCommit` | M5/M8 | No | No | Open |
| B3-L3 | Low | `config/queue.php:106,125` | Open (with F10) | SQLite fallback for failed jobs | Decide with F10 | M2 | No | Yes | Open; related: F10 |
| B3-L4 | Low | `backend/.env.example` | Open | Dev defaults copied to production | Production checklist | M11 | No | No | Open |
| B4-L1 | Low | `SanctumTokenExpiryTest.php:32-40` | Open | Per-token expiry looks tested but isn't | 10-minute token variant | M2 (before ADR-016 endpoints) | No | No | Open |
| B4-L2 | Low | `phpunit.xml:24-28,38-40` | Open | Shell can override test env | `force="true"` | M2 | No | No | Open |
| B4-L3 | Low | `phpstan.neon` paths | Open | Guard code not in Larastan CI | Add `tests/Support`, `tests/TestCase.php` | M2 | No | No | Open |
| B5-L1 | Low | `composer.json:45` | Open | Unpinned `npx concurrently` | Pin or remove | Any | No | No | Open |
| B5-L2 | Low | `composer.json` config | Open (tied to U14) | Lock may drop PHP 8.3 | `platform.php` pin | Before next `composer update` | No | Yes (U14) | Open |
| B5-L3 | Low | `config/mail.php:17,82-89` | Open | Mail/OTP text in logs | Real provider in production | M2/M11 | No | No | Open |
| B5-L4 | Low | `config/logging.php` | Open | No rotation, request ID or redaction | Request-ID + redaction (M2); prod level/channel (M11) | M2/M11 | No | No | Open; outside M1 scope per D9 |
| B7-L2 | Low | `.gitignore:39,72` | Open | `build/`, `tmp/` hide folders anywhere | Anchor patterns | Before such folders exist | No | No | Open |
| B7-L3 | Low | `.gitignore:20-21` | Open | `debug.keystore` outside `android/` trackable | Remove exception | Before mobile signing work | No | No | Open |
| B8-L1 | Low | `packages/api-types/README.md:3`, `package.json:5` | Open | Placeholder described as existing | Mark planned | Any / M2 | No | No | **Partly fixed 2026-11-18 (D2):** README corrected. **Still open:** `package.json:5` description (manifest field, outside the documentation-only scope). Related: B12-L2, F14. **Post-remediation 2026-11-19: Fixed;** `package.json:5` description corrected under separate owner authorization (§16) |
| B9-L1 | Low | `infrastructure/README.md:23` | Open | Wrong `core.autocrlf` statement | Correct text | Any | No | No | **Fixed 2026-11-18 (D2)** |
| B9-L2 | Low | `apps/admin/.oxlintrc.json:2` | Open | Editor schema path broken | `../../node_modules/...` | Any | No | No | Open (configuration file, not documentation) |
| B10-L1 | Low | `apps/mobile/package.json:11` | Open | Lint warnings never fail; 1 exists | Fix F11 first, then `--max-warnings 0` | Before M2 | No | Yes (policy) | Open; related: F11, B10-I1 |
| B11-L1 | Low | `apps/mobile/.gitignore:39` | Open | `example` ignored anywhere | Anchor or remove | Any | No | No | Open |
| B12-L1 | Low | `apps/mobile/AGENTS.md:16,21,34,40` | Open | Agents told to change deps / use EAS | Add NOBTA guardrails | Before agent-assisted M2 work | No | Yes | Open |
| B12-L2 | Low | `SYSTEM_ARCHITECTURE.md:39,44`, `API_CONVENTIONS.md:119`, `DATABASE_STRATEGY.md:63` | Open (overlaps F14) | Planned items read as existing | Mark (M2)/(M3) | Any | No | No | **Fixed 2026-11-18 (D2)**; related: F14, B8-L1 |
| B12-L3 | Low | `SYSTEM_ARCHITECTURE.md:12` | Open | Diagram cites ADR-004 cookie-or-token | Cite ADR-019 | Any | No | No | **Fixed 2026-11-18 (D2)**; same root as B13-L2 |
| B12-L4 | Low | `DEPENDENCY_AUDIT.md:13-25` (+ ROADMAP U17 line 40, B13-I2) | Open | Audit figures stale (today: 2 advisories, 28/21 entries, all moderate; gate exit 0) | Dated re-verification | At M1 sign-off re-review (U17 condition) | No | Yes (U17 re-review) | **Fixed 2026-11-18 (D2)**; figures acknowledged (D4); duplicate: B13-I2 |
| B13-L1 | Low | `ARCHITECTURE_DECISIONS.md:6` (overlaps F12) | Open | Only ADR-021 is formally Accepted; ADR-019 still Proposed | Status index; owner accepts/keeps proposed | Before M2 | No | **Yes** | Open; decide before M2 (D3); duplicate: F12. **Post-remediation 2026-11-19:** (a) the header contradiction at `ARCHITECTURE_DECISIONS.md:6` is fixed, with no ADR status changed; (b) which ADRs to accept stays an owner decision, classified by the owner as an M2 item under D3 (§16) |
| B13-L2 | Low | `ROADMAP.md:28` (U5) | Open | U5 still cites ADR-004 choice | Cite ADR-019, conditional on U5 | Any | No | No | **Fixed 2026-11-18 (D2)**; U5 status unchanged; same root as B12-L3 |
| B13-L3 | Low | ADR-018:320, `DATABASE_STRATEGY.md:54` | Open | Planned `sessions` table collides with Laravel `sessions` | Rename (e.g. `class_sessions`) | Before M5 | No | Yes | Open |
| B13-L4 | Low | ADR-002:30 | Open | Module arch tests promised for M1, not in "moved to M2" list | Timing note, or fold into B2-L4 | Before M2 | No | **Yes** | Open; decide before M2 (D3); related: B2-L4. **Post-remediation 2026-11-19:** classified by the owner as an M2 item under D3, not an M1 C19 failure (§16) |

### 4.3 Informational entries

None of these blocks M1. Exceptions that need a decision or have a dependency are noted. **F12 is the same issue as Low finding B13-L1 and needs an owner decision (D3).**

| IDs | Summary | Status |
|---|---|---|
| F9 | Web guests redirect to a missing `login` route | Deferred M2 |
| F10 | `database.php:20` falls back to `sqlite`; stray ignored `backend/database/database.sqlite` still exists (related: B3-L3) | Open; decide with B3-L3 |
| F11 | UTF-8 BOM in `apps/mobile/__tests__/home-screen.test.tsx` (re-confirmed 2026-11-18); causes the B10-L1 warning (duplicate: B10-I1) | Open (code file, outside D2 scope) |
| F12 | Duplicate of **B13-L1**; needs an owner decision before M2 (D3) | Open, **decision**. *Post-remediation 2026-11-19:* header part fixed; the decision part is an M2 item (D3) (§16) |
| F13 | Gulf/KWD examples vs ADR-014 Egypt (duplicate: B13-I7) | Open until U1. *Post-remediation 2026-11-19:* classified by the owner as an M2 item under U1 (§16) |
| F14 | "CI fails on drift" in present tense (part of B12-L2) | **Post-review:** Fixed 2026-11-18 (D2) |
| F15 | `shared-config/README.md:8` example wrong (duplicate: B8-I1) | **Post-review:** Fixed 2026-11-18 (D2) |
| B2-I1–I9 | No rate limiting (U19); users schema and token `device_id` are M2; no token pruning; `sessions.user_id` no FK; `/` advertises `/api/v1` (B2-I6: same kind as B7-L4/B6-L1, but in code); mass assignment safe; timezone consistent; password broker unused | Deferred/none |
| B3-I1–I7 | Future admin session cookies; limiter writes to DB cache (decide with U19); `serializable_classes` strength; storage, app settings, framework migrations; factory test data | None/deferred |
| B4-I1–I7 | Stale test title; arch tests; DB connection, file serving, seeder tests; placeholder unit test; leftover CORS path `sanctum/csrf-cookie` | None |
| B5-I1–I9 | Slack log level; stock services, `index.php`, `.htaccess`; `.env.example`; composer scripts and lock clean; env ignore coverage; `config:clear` before tests | None |
| B6-I1–I7 | README accurate; Pint preset; `.editorconfig`/`.gitattributes` overrides; `robots.txt` allows all (decide with U5); empty favicon; root tooling | None/U5 |
| B7-I1–I6 | Editorconfig and attributes consistent; ignore coverage; lockfile consistent; deprecated packages incl. `eslint@9.39.5` | None |
| B8-I1–I6 | F15 confirmed (B8-I1 = F15, fixed 2026-11-18); exports map; shared base reaches admin; `typecheck` writes `.tsbuildinfo` under `node_modules`; test file in app program | None |
| B9-I1–I8 | Admin placeholder; metadata; RTL-ready CSS; Vite favicon (branding placeholder); oxlint rules; ignores; READMEs | U7 for favicon |
| B10-I1–I9 | F11 evidence (B10-I1 = F11); unused template deps; `app.json`, `eas.json`, tsconfig, routing, home screen, smoke test, traceability | None |
| B11-I1–I8 | All mobile assets are Expo template art (U18); technically valid; branding inconsistent across apps | U7/U18 |
| B12-I1–I9 | AGENTS.md template; `.claude` plugin enablement; VS Code files; API/DB/architecture/design/product docs notes | None |
| B13-I1–I8 | U20 "58/58" historical; U17 stale (B13-I2 = B12-L4, fixed 2026-11-18); M0 wording; ROADMAP doesn't record the 2026-11-12 hardening; `*.sqlite*` breadth; compiled view (covered in Batch 14); Gulf vs Egypt (B13-I7 = F13); `Idempotency-Key` scope ADR-018 vs API_CONVENTIONS | None/M5 |

**Raw vs unique count.** The informational entries are **not all unique**:
- **100 raw entries:** F9–F15 (7) plus 93 batch I-items.
- **3 duplicate a Low finding in §4.2:** F12 = B13-L1, F14 ⊂ B12-L2, and B13-I2 = B12-L4.
- **3 pairs duplicate each other within §4.3:** F13 = B13-I7, F15 = B8-I1, and F11 = B10-I1.
- **Unique informational observations: 94** (100 − 3 − 3).

**Possible elevation** (raised in the owner's review; classification unchanged):
- **B12-I2:** the `.claude/settings.json` file automatically enables an external plugin. It's an agent supply-chain issue, related to B12-L1.
- **B13-I8:** the `Idempotency-Key` scope gap, of the same weight as B13-L3.
- **B3-I2:** limiter writes to the database cache; becomes relevant when U19 is decided.

### 4.4 New findings from this assessment

| ID | Sev. | Area | Evidence | Impact | Action | M1? | Owner? |
|---|---|---|---|---|---|---|---|
| A1 | Medium (evidence gap) | Backend test suite | Last full Pest run 58/58 on 2026-11-11. After the 2026-11-12 changes, only DB-free subsets ran (46 + 7 guard tests; ExampleTest 9; 70 tests in one run; 17 in Batch 4). `DatabaseConnectionTest`, `SanctumTokenExpiryTest` and `SeederEnvironmentTest` have not run since | ROADMAP Stage F ("all pass locally") describes the 2026-11-11 state, not the current code | Run the full suite on MySQL. D5 prefers a local run on `nobta_testing`, but **database access and execution need separate authorization**. The first CI run would also provide it. **Post-assessment (2026-11-19): CLOSED.** The full suite passed on MySQL `nobta_testing` in an authorized local run: 94 passed, 165 assertions, exit 0 (§15) | **Yes** (as assessed); closed 2026-11-19 | Yes |
| A2 | Low (process) | U15 human-review condition | U15 (ROADMAP:38) requires "a **human review** of all untracked files". Batch 1 framed the AI explanations as "preparation material only… doesn't satisfy U15". The Batch 14 report later said 130/130 "satisfies" that part of U15; that statement was overstated | D1: the 130/130 AI-assisted review is accepted as **detailed inventory review evidence**. It supports the human review; it does not replace the owner's final check | Before the first commit, the owner checks the **exact staged file list** and the **secret-scan summary** (pre-commit audit, §10) | **Yes** | **Yes** |
| A3 | Informational | This report | Adds a 131st untracked file under `docs/` | Must be covered by the pre-commit review | Include in the review before the first commit (D8: kept as a historical record) | No | No |
| A4 | Informational | `ROADMAP.md:49` | Says the 130-file review "is not complete". The inventory review is complete (130/130) and is accepted as detailed evidence (D1); the owner's final staged-list check (A2) is still outstanding | Status text is outdated; not edited (outside the D2 list) | Update when the owner records the outcome. **Post-remediation (2026-11-19): CLOSED.** `ROADMAP.md:49` now states the completed 130/130 review (D1) and the outstanding A2 check (§16) | No | Yes |

### 4.5 Batch 1 items left out of the original register (added 2026-11-18)

Batch 1 issued no finding IDs. The IDs below are assigned now for traceability only. The categories stay distinct: owner questions, a concern (finding), and an optional suggestion.

**Owner questions from Batch 1** (the "answer before approving Batch 1" list):

| ID | Question | Status | Related accepted decision (does not by itself answer the question) |
|---|---|---|---|
| B1-Q1 | Is the CI trigger (push to `main` plus all pull requests) correct? | Open: answer not recorded | — (D7 notes that a push to `main` triggers CI) |
| B1-Q2 | Accept the throwaway CI credentials (`ci-password`, `ci-root`) in plain text in the workflow? | Open: answer not recorded | — |
| B1-Q3 | Accept that the audit gate depends on the live advisory feed and ignores dev-only dependencies? | Open: answer not recorded | U17 (gate added 2026-11-11) |
| B1-Q4 | Accept that the CI migrate step is protected only by the job env, not by the test guard? | Open: answer not recorded | — |
| B1-Q5 | Accept that the guard checks driver and database but not the host? | **Resolved by F5** (host allowlist added 2026-11-12) | — |
| B1-Q6 | Accept the boot-time limitation (a named connection resolved before the listener isn't checked)? | Open: answer not recorded | ADR-021 D5 documents it as a limitation |
| B1-Q7 | Is a 30-minute Sanctum backstop correct for all personal access tokens? | Open: answer not recorded | ADR-021 D1 (Accepted 2026-11-11) |
| B1-Q8 | Should CORS stay deny-all with credentials off until U5 is decided? | Open: answer not recorded | ADR-021 D8 (Accepted 2026-11-11) |
| B1-Q9 | Accept `DatabaseTransactions`, including migrating local `nobta_testing` manually when migrations are added? | Open: answer not recorded | U20 resolved 2026-11-11; DATABASE_STRATEGY §6 |

**Concern and suggestion from Batch 1:**

| ID | Type | Item | Classification | Status | M1? |
|---|---|---|---|---|---|
| B1-C1 | Concern (finding) | CI uses the floating image tag `mysql:8.4`, while local is 8.4.3. Patch versions can drift between runs | Low; owner decision (pin the patch or not). Requires a CI change, so it is outside D2 | Open | No |
| B1-S1 | Optional suggestion | A Sanctum `token_prefix` would help secret scanning detect leaked tokens | Optional, not a finding. Requires a configuration change | Open (optional) | No |

The other Batch 1 concerns (F5, F7, F9, F10) were already registered as F-items.

**Totals:**

| Group | As assessed | After the D2 remediation |
|---|---|---|
| Fixed | 11 | 11 + 5 Low (B9-L1, B12-L2, B12-L3, B12-L4, B13-L2) |
| §4.2 rows | 31 (30 open + B2-L2 accepted temporarily) | 25 open (B8-L1 partly fixed but still open) + B2-L2 accepted temporarily |
| §4.3 informational | 100 raw / 94 unique | F14, F15 (= B8-I1) and B13-I2 (= B12-L4) are now fixed |
| §4.4 new items | 4 (A1–A4) | All still open after D2. *Post-assessment (2026-11-19):* A1 closed (§15); A2–A4 still open |
| §4.5 Batch 1 items | — (added) | 8 open questions, 1 resolved question (Q5), 1 open Low concern, 1 optional suggestion |

Nothing was closed or downgraded because of rewording. Items marked fixed were fixed in the files themselves (§14).

## 5. M1 criteria assessment

Key: **PASS** = verified by available evidence; **FAIL** = unmet; **BLOCKED** = cannot be verified with the evidence available; **ACCEPTED RISK** = owner-accepted with scope and date; **DEFERRED** = moved to a later milestone.

**Why 23 criteria:** they are numbered C1–C22, but C8 is split into C8a (API error handling) and C8b (logging production readiness). That gives 23 assessed rows.

| # | Criterion | Status | Evidence and date |
|---|---|---|---|
| C1 | Backend foundation and Laravel configuration | PASS | Laravel 13.40.0, Sanctum 4.3.3, Pest 4.7.8, Larastan 3.14.0, Pint 1.32.1 (`composer.lock`, 2026-11-17); Larastan level 8, 0 errors (2026-11-12); `pint --test` whole backend passed (2026-11-15) |
| C2 | TestDatabaseGuard and database isolation safety | PASS | Guard requires `mysql` + `nobta_testing` + local host after `DB_URL` parsing; 46 unit + 7 wiring tests passed DB-free (2026-11-12). Documented limitations: loopback tunnels, `DB_SOCKET`, reflection on `readPdoConfig`. *Note:* whether the guard accepts the valid local configuration end to end is only shown by a full MySQL run (C15). *Post-assessment (2026-11-19):* shown; the guard accepted the effective configuration and the live connection, and all guard tests passed on MySQL (§15) |
| C3 | API authentication and Sanctum decisions | PASS | 30-minute backstop, `sanctum → users`, `routes=false`, JSON 401 for `api/*` (config read 2026-11-17/18); ExampleTest 9/9 DB-free (2026-11-12). *Note:* this rests on configuration plus DB-free tests; the MySQL-backed expiry tests are under C15. *Post-assessment (2026-11-19):* `SanctumTokenExpiryTest` (5 tests) passed on MySQL (§15); B4-L1 remains open |
| C4 | CORS and security configuration | PASS | Empty origin allowlist from `CORS_ALLOWED_ORIGINS`, credentials off, `local` disk `serve=false`; CorsTest and LocalFileServingTest passed DB-free (2026-11-12, 2026-11-15) |
| C5 | Database migration strategy | PASS (static) | ADR-021 D4; 4 migrations, each with `down()`; policy in DATABASE_STRATEGY §5. Applied state in `nobta_testing` not verifiable without DB access |
| C6 | Environment and secret safety | PASS | Secret-pattern scan of all 130 files (2026-11-18): no real secrets. Hits are ephemeral CI credentials, `null` placeholders, test-fixture URLs, local doc paths and public package-author emails. `backend/.env` present locally and ignored; `.env.example` trackable by design |
| C7 | Dependency and vulnerability audit | ACCEPTED RISK | U17 ACCEPTED TEMPORARILY 2026-11-11 (moderate/low tooling advisories). Re-run 2026-11-17: high/critical gate exit 0; 2 moderate advisories; `composer audit` clean. Figures acknowledged by the owner on 2026-11-18 (D4) |
| C8a | API error handling | PASS | `bootstrap/app.php:17,21` JSON for `api/*`; UnauthenticatedResponseTest passed DB-free (2026-11-15) |
| C8b | Logging production readiness | DEFERRED | B5-L4: request ID, redaction (M2); level and channel (M11). Owner confirmed it is outside M1 scope (D9, 2026-11-18) |
| C9 | Admin scaffold | PASS | `tsc --noEmit`, `vite build` (to scratchpad), Vitest 1/1, oxlint `--deny-warnings` (Batch 9, 2026-11-17) |
| C10 | Mobile scaffold and Expo configuration | PASS | `expo config` offline, `app.json`/`eas.json` reviewed (Batch 10); `expo-doctor` 20/20 (2026-11-11); assets valid (Batch 11) |
| C11 | Android dev build on emulator | PASS | Stage H, 2026-11-11: EAS build `bba4ea42…` FINISHED, APK verified, installed and rendered with Metro. Recorded evidence; not re-run |
| C12 | Mobile linting | PASS | ESLint exit 0 (Batch 10, 2026-11-17). One BOM warning tolerated (B10-L1, F11); no zero-warning policy is defined for mobile (owner decision) |
| C13 | TypeScript validation | PASS | Mobile `tsc --noEmit` exit 0 (TS 6.0.3, strict); admin `tsc` exit 0 (TS 7.0.2) (Batches 9–10, 2026-11-17) |
| C14 | Admin and mobile tests | PASS | Vitest 1/1 (Batch 9) and Jest 1/1 (Batch 10), both 2026-11-17; smoke tests only |
| C15 | Backend full test suite on MySQL | **BLOCKED** (as assessed). *Post-assessment (2026-11-19):* **PASS** | See A1: last full run 2026-11-11 (58/58), before the 2026-11-12 changes. *Post-assessment (2026-11-19):* the full current suite passed on MySQL 8.4.3 `nobta_testing` through `composer test`: 94 passed, 165 assertions, 11 suites, 0 failures, exit 0. This was a local run, not CI; CI evidence stays under C17 (§15) |
| C16 | CI workflow configuration (static) | PASS (static) | YAML parsed (js-yaml) and Prettier-checked; action tags exist (`git ls-remote`); audit gate, `APP_ENV: testing`, `migrate --force` before Pest. No `actionlint`. F7 and B1-C1 open. Execution evidence is C17 |
| C17 | GitHub CI execution evidence | **BLOCKED** | Never run: 0 commits, 0 remotes (U15) |
| C18 | Human review of untracked files (U15) | **BLOCKED** | 130/130 AI-assisted review complete and accepted as detailed evidence (D1); the owner's final staged-list and secret-scan check is still required (A2); this report adds a 131st file (A3) |
| C19 | Documentation accuracy and ADR consistency | **FAIL** (low severity, as assessed) | Open documentation findings at assessment time: B8-L1, B9-L1, B12-L2, B12-L3, B12-L4, B13-L1, B13-L2, B13-L4, F12–F15. The milestone rule "docs updated" (ROADMAP line 3) was not fully met. **Post-review:** partly remediated under D2 (§14); to be re-assessed in the final M1 assessment. *Post-remediation (2026-11-19):* **PASS.** All objectively correctable items are fixed: A4, B8-L1, the B13-L1 header and N1 (§16). The remaining items (B13-L1/F12 decision part, B13-L4, F13) were classified by the owner as M2 items under D3 and U1; no decision or status was changed. The final M1 assessment confirms this |
| C20 | Deferred M2 work | DEFERRED | Six items moved 2026-11-11 (§8) |
| C21 | Branding and identity (U7, U18) | DEFERRED | U7 "needed by M1–M2" in ROADMAP. The owner confirmed branding is outside M1 scope and stays subject to the U7/U18 decisions (D9, 2026-11-18); U-item statuses unchanged |
| C22 | Project integrity and Git baseline | PASS | 130 hashes unchanged; 15/0/0/0 (2026-11-18, at assessment time) |

**Counts (as assessed):**

| Status | Count |
|---|---|
| PASS | 15 (C5 and C16 rest on static review only) |
| FAIL | 1 |
| BLOCKED | 3 |
| ACCEPTED RISK | 1 |
| DEFERRED | 3 |
| **Total** | **23** |

*Post-assessment (2026-11-19):* with the A1 evidence, C15 now reads PASS. A tally as of 2026-11-19 would therefore be 16 PASS, 1 FAIL (C19), 2 BLOCKED (C17, C18), 1 ACCEPTED RISK and 3 DEFERRED. The as-assessed counts above are kept, and the final M1 assessment re-evaluates every criterion.

*Post-remediation (2026-11-19):* with C19 also PASS (§16), the tally as of the end of 2026-11-19 is 17 PASS, 0 FAIL, 2 BLOCKED (C17, C18), 1 ACCEPTED RISK and 3 DEFERRED.

No local result is counted as CI evidence. Local runs and CI are kept separate in §6.

## 6. CI gap assessment

| Level | Status | Evidence |
|---|---|---|
| Workflow source reviewed | Yes | Pre-review, Batch 1, F5 change (2026-11-12) |
| Workflow syntax validated | Partly | Prettier and js-yaml parse; action tags exist. `actionlint` not run (not installed) |
| Local commands executed | Yes, separately | `npm run check` passed (2026-11-11); since then, per-workspace commands (Batches 9–10, 2026-11-17); Pint, Larastan |
| Tests executed locally | Partly (as assessed). *Post-assessment (2026-11-19):* Yes | Vitest 1/1, Jest 1/1, Pest DB-free subsets; **full Pest on MySQL not since 2026-11-11** (A1). *Post-assessment (2026-11-19):* the full Pest suite passed on MySQL `nobta_testing` (94 tests, local run; §15) |
| CI executed on GitHub | **No** | 0 commits, 0 remotes |
| CI results available | **No** | — |

**Evidence still needed for U15 and M1:**

1. The owner's final check of the exact staged file list and the secret-scan summary (A2, D1), covering every file to be committed, including this report and the files changed by D2.
2. An owner-approved first commit, then a separately approved push to a private GitHub repository (D6, D7). Both are Git mutations. **Neither is authorized yet.**
3. A completed GitHub Actions run of `.github/workflows/ci.yml` with both jobs green on that commit:
   - **`js`:** `npm ci`, the audit gate, `npm run check`, `expo-doctor@1.21.6`, and the admin build.
   - **`backend`:** Pint, Larastan, `migrate --force`, and the **full Pest suite on MySQL 8.4**. This also closes A1, if it hasn't been closed by an authorized local run (D5). *Post-assessment (2026-11-19):* A1 was closed by the authorized local run (§15). The CI run is still required for C17.
4. The run URL or ID, commit SHA and date, recorded with the sign-off (a documentation change that needs its own authorization).

**Known risks on a first run:**
- `expo-doctor` needs network access.
- There are no job timeouts (F7).
- The image tag `mysql:8.4` is floating (B1-C1).
- The mobile lint warning does not fail the job (B10-L1).

Checked read-only during the review: the lockfile includes the Linux x64 bindings for oxlint, rolldown and lightningcss, so the Windows-generated lockfile is not expected to break `npm ci` on Ubuntu.

## 7. Owner decisions and open items

### 7.1 Owner decisions D1–D9 (approved 2026-11-18)

These record scope and intent only. **No Git, repository, account or database action is authorized by them.**

| ID | Decision |
|---|---|
| D1 | The 130/130 AI-assisted review is accepted as detailed inventory review evidence. The owner's final review of the exact staged file list and the secret-scan summary is still required before the first commit |
| D2 | The listed documentation findings (B8-L1, B9-L1, B12-L2, B12-L3, B12-L4/B13-I2, B13-L2, F14, F15) are fixed within the documentation-only scope authorized on 2026-11-18 (§14) |
| D3 | ADR status questions (B13-L1/F12) and ADR-002 arch-test timing (B13-L4) are decided before M2. ADR statuses are unchanged |
| D4 | U17's 2026-11-17 audit figures are acknowledged; U17 stays ACCEPTED TEMPORARILY. No new audit or dependency change |
| D5 | A local full Pest run against `nobta_testing` is preferred for A1. **Database access and execution are not authorized** and need a separate approval |
| D6 | The target GitHub repository is private, and commits use a noreply email. No repository creation or account change is authorized now |
| D7 | Commit and push need separate approvals. A push to `main` triggers CI automatically |
| D8 | This assessment report is kept as a historical record, corrected to reflect the review and the decisions |
| D9 | Logging (C8b) and branding (C21) are outside M1 scope. Logging stays in M2/M11 planning; branding stays subject to U7/U18. U-item statuses are unchanged |

### 7.2 Still needed before M1 sign-off

| Item | Status | What is needed |
|---|---|---|
| U15 / F1 / A2 | Open | Owner's final staged-list and secret-scan check; separate approvals for the first commit and for the push to a private GitHub repository |
| A1 | **Closed 2026-11-19** (was Open) | The full Pest suite passed on MySQL in an authorized local run (§15). Nothing further is needed for A1; CI must still run the suite for C17 |
| C19 remainder | **Closed 2026-11-19** (was Open) | B8-L1 (`package.json:5`), A4 (ROADMAP:49) and the newly found N1 (`SYSTEM_ARCHITECTURE.md:78`) are fixed, and so is the B13-L1 header. F13 (U1), the B13-L1/F12 decision part and B13-L4 (D3) were classified by the owner as M2 items. The final assessment confirms C19 (§16) |
| B1-Q1–Q4, Q6–Q9 | Open | Owner answers (§4.5) |
| Recording the result | — | ROADMAP M1 status, CI run ID and A4 wording, each under its own authorization |

### 7.3 Needed before M2 starts or early in M2

| Item | Why |
|---|---|
| U1 Market (ADR-014 proposed) | Currency, providers, data protection; needed by M2 |
| U3 Login method (ADR-015 proposed); OTP/SMS provider | M2 auth flow |
| U14 PHP 8.4 | Pest 5, B5-L2 |
| U19 Rate-limit numbers (ADR-021 D6) | Required before the first auth or OTP endpoint |
| U21 Existing table collation | Before the first M2 migration |
| B13-L1 / F12 ADR acceptance (D3) | ADR-003/005/016/017/019/020 underpin M2 but are formally Proposed |
| B13-L4 ADR-002 module arch tests timing (D3) | M1 promise not in the moved list |
| B10-L1 mobile warning policy; B12-L1 agent guardrails | Development hygiene |
| U5 Hosting topology (partly) | CORS origins, viability of the ADR-019 admin cookie flow; needed by M1/M9 per ROADMAP |

### 7.4 Can safely stay open until later

| Item | Needed by |
|---|---|
| U2 Payments | M5 |
| U6 Monetization | M3 |
| U7 Brand identity | M1–M2 per ROADMAP; outside M1 sign-off scope per D9 |
| U18 App images (with U7) | Store readiness |
| U8 Digits | M6 |
| U9 Custom roles | M7 |
| U10 Retention | M11 |
| U12 Discovery model | M6 |
| U13 App identifiers | Before the first production or store build |
| U16 Windows native toolchain | When local native builds are needed |
| B13-L3 `sessions` naming | Before M5 |

Already resolved: U4, U11, U20.

## 8. Deferred work assessment

| Item (moved 2026-11-11) | Implemented? (checked 2026-11-18) | Described as implemented anywhere? | Deferral still valid? |
|---|---|---|---|
| OpenAPI generator (ADR-020) | No: no `dedoc/scramble` in `composer.lock`; `api-types` is a placeholder | At assessment: **yes, in places** (B8-L1, B12-L2, F14). **Post-review:** corrected 2026-11-18, except the `packages/api-types/package.json` description (manifest, still open) | Yes |
| Mobile i18n library (ADR-012) | No i18n dependency in mobile | No | Yes |
| Admin router | No router dependency in admin | No | Yes |
| Design token package | None | No | Yes (values depend on U7) |
| RTL lint rules | No `left`/`right` restrictions in oxlint or ESLint configs | No (DESIGN_GUIDELINES §3 says M2) | Yes |
| Shared ESLint/Prettier presets | `shared-config` has only `tsconfig/base.json` | No | Yes |

Other deferred items found in the records:
- U19 rate limits (M2, required before auth).
- B4-L1 (M2).
- B2-L3 (M2).
- B3-L2 (M5/M8).
- B5-L3 and B5-L4 (M2/M11).
- B3-L4 (M11).
- F9 (M2).

**Not in the moved list:** the ADR-002 module arch tests promised "in M1" (B13-L4). Per D3, this is decided before M2.

No deferred item was authorized or started.

## 9. Risk matrix

**Scoring method:** no risk-scoring method was defined in the project records or in this assessment. The likelihood, impact and severity values below are the reviewer's qualitative judgments.

They are **not consistent under any single matrix**:
- R1 rates Medium × Medium as **High**.
- R3 rates Low × High as **Medium**.
- R9 rates High × Low as **Low**.

The ratings are left as originally assessed and are **labelled inconsistent pending owner clarification** of a scoring method. They should not be compared across rows as if they came from one formula.

| ID | Risk | Likelihood | Impact | Severity (unreconciled) | M1 sign-off impact | Mitigation | Owner decision |
|---|---|---|---|---|---|---|---|
| R1 | First GitHub Actions run fails (never executed; environment differs from local) | Medium | Medium | **High** | Blocks (C17) | Run CI, fix failures before sign-off; add timeouts (F7) | Yes |
| R2 | MySQL-backed tests fail after the 2026-11-12 guard and Sanctum changes | Low–Medium | Medium | **Medium** | Blocks (C15) | Full Pest on MySQL (authorized local run or CI). *Post-assessment (2026-11-19):* mitigated locally; the full suite passed on MySQL (§15). The CI run is still pending (R1) | Yes |
| R3 | Secret or local-only file included in the first commit | Low (scan clean, ignores verified) | High | **Medium** | Blocks until the owner's final check (C18, A2) | Owner check of the staged list and secret-scan summary; `git status` check before the first `git add` | Yes |
| R4 | M2 built on formally unaccepted ADRs (016, 017, 019, 020) or stale docs | Medium | Medium | **Medium** | No (affects M2) | Accept or keep each ADR before M2 (D3) | Yes |
| R5 | Moderate tooling advisories (glob, minimatch) | Low (no user input path; not in bundles) | Low–Medium | **Low** | Accepted risk (U17, D4) | Keep CI gate; re-review per conditions | Yes (re-review) |
| R6 | Development defaults reach production (debug, mail `log`, debug logging, demo seeding in `local`) | Low before M11 | High | **Medium (later)** | No | Production checklist in M11; B3-L4, B5-L3, B5-L4 | No |
| R7 | Agent tooling changes dependencies or submits builds (AGENTS.md, Expo plugin) | Low | Medium | **Low** | No | NOBTA guardrails in AGENTS.md (B12-L1) | Yes |
| R8 | Lint warnings accumulate unnoticed in mobile | Medium | Low | **Low** | No | F11 then `--max-warnings 0` (B10-L1) | Yes |
| R9 | `sessions` table name collision blocks the M5 schema | High (if unchanged) | Low (caught at migration) | **Low** | No | Rename before M5 (B13-L3) | Yes |
| R10 | App identifier or EAS account ownership is hard to change later | Low now | High at store time | **Low (now)** | No | U13 before the first production build; consider organization ownership | Yes |

## 10. Minimum sign-off prerequisites and sequence

Proposed order (updated after the owner's review). Each step that changes Git, a repository or the database needs its own explicit approval. **None is authorized by this report.**

1. ~~Review this assessment report.~~ Done 2026-11-18 (ACCEPT WITH CORRECTIONS).
2. ~~Owner decisions D1–D9.~~ Approved 2026-11-18 (§7.1).
3. ~~Documentation-only remediation (D2).~~ Done 2026-11-18 (§14).
4. ~~*(Optional, needs separate database authorization; D5 preference)*: an authorized local full Pest run on `nobta_testing`, to close A1 before the first commit.~~ Done 2026-11-19 under separate authorization: 94 passed, 165 assertions, exit 0 (§15).
5. **Final pre-commit audit.**
   - Re-hash every file and review the lines changed since the 130/130 review, including D2 and this report.
   - The owner checks the exact staged file list and the secret-scan summary (A2, D1).
   - Answer or carry forward B1-Q1–Q4 and Q6–Q9.
6. **First commit.** Separate approval, using the noreply identity (D6, D7).
7. **Push to a private GitHub repository.** Separate approval; creating the repository is also unauthorized until then.
   - The push to `main` **triggers CI automatically** (D7).
   - If CI fails, any fix needs its own authorization.
8. **A green GitHub Actions run of both jobs**, including the full Pest suite on MySQL. Record the run ID, commit SHA and date.
9. **Final M1 assessment.** This re-evaluates C15, C17, C18 and C19. Recording the outcome in ROADMAP (M1 status, A4) needs authorization.

After these, the expected verdict is **READY WITH OWNER-ACCEPTED RISKS**: U17, plus any remaining documentation items the owner accepts. It would be **READY FOR M1 FINAL SIGN-OFF** only if no C19 item remains unresolved or unaccepted.

## 11. Final verdict

**NOT READY FOR M1 FINAL SIGN-OFF.** This is unchanged by the corrections.

The scaffolding, hardening and local verification are in good shape, and no Critical or High *defect* is open. F1 remains a process blocker. The blocking items are evidence, process steps and decisions:

- CI has never run on GitHub (C17, F1).
- The full MySQL-backed test suite has not run on the current code (C15, A1). *Post-assessment (2026-11-19): resolved;* it passed on MySQL `nobta_testing` (§15). The verdict stays NOT READY because of the remaining items.
- The owner's final staged-list and secret-scan check is still required (C18, A2).
- Documentation drift was not resolved at assessment time (C19). It was partly remediated on 2026-11-18 and is re-assessed at step 9. *Post-remediation (2026-11-19): resolved* for M1 (§16). The verdict stays NOT READY because of C17 and C18.

Readiness for sign-off is not authorization for M2. M2 stays not authorized until the owner signs off M1 and authorizes M2 explicitly.

## 12. Evidence limitations

- **No database access.** The applied migrations, existing table collations (U21) and server defaults are taken from earlier records and were not re-verified. *Post-assessment (2026-11-19):* the authorized A1 run verified, read-only, the four applied migrations, the ten tables and their `utf8mb4_unicode_ci` collation in `nobta_testing` (§15). The development database `nobta` was not accessed.
- **No tests, builds or audits re-run for this assessment, its review, or the D2 remediation.** Results come from dated runs in this session (2026-11-11 to 2026-11-17).
- **Stage H (EAS build and emulator) and `expo-doctor` 20/20** are recorded evidence from 2026-11-11 and were not repeated.
- **Network-dependent results change over time.** These include the `npm audit` figures and the action tag existence.
- **`actionlint` was not run.** Workflow semantics are unverified until GitHub runs them.
- **Owner acceptance is not in repository history.** It is recorded only in the owner's review prompts; batch reports from Batch 9 on carried the reviewer's recommended decision.
- **The findings register** was consolidated from this session's batch reports. Fixed items were re-checked against the current files; open items were not re-tested individually unless noted.
- **Batch 1 owner questions:** the records do not show whether they were answered (§4.5).
- **The risk ratings** have no defined scoring method (§9).

## 13. Statement of changes

**Original assessment (2026-11-18):** read-only, apart from creating this file.

**Report review (2026-11-18):** read-only; no file changed.

**Documentation remediation (2026-11-18, authorized under D2):** this report was corrected, and the documentation files listed in §14 were edited.

**A1 evidence run (2026-11-19, separately authorized):** Laragon MySQL was started, `nobta_testing` was verified read-only, and `composer test` ran. No project file was changed by the run (§15).

**A1 documentation update (2026-11-19, separately authorized):** only this report and `docs/architecture/ROADMAP.md` (the M1 Validation row and Stage F) were edited to record the evidence.

**C19 reassessment (2026-11-19):** read-only; no file changed.

**C19 documentation remediation (2026-11-19, separately authorized):** exactly five files were edited: this report, `docs/architecture/ROADMAP.md` (line 49), `packages/api-types/package.json` (description only), `docs/architecture/SYSTEM_ARCHITECTURE.md` (line 78) and `docs/architecture/ARCHITECTURE_DECISIONS.md` (introductory line 6). No ADR status, U-item or owner decision changed, and the lockfile is untouched (§16).

Across all three phases:
- No application source, test, configuration, migration, CI workflow, manifest or lockfile was changed.
- No file was deleted or renamed, and no file was created other than this report.
- No Git mutation: nothing staged, committed or pushed, and no branch, tag or remote created.
- No database connection, query or migration; no service, server or emulator started or stopped.
- No dependency or audit decision changed, and no audit was re-run during remediation.
- No U-item, ADR, milestone or M1 status changed.
- M2 was not started.

Read-only commands used: Git status and inventory checks, SHA-256 hashing, `grep` and `sed` reads, `git check-ignore`, and a Node secret-pattern scan writing only to the scratchpad.

## 14. Post-review corrections and remediation log (2026-11-18)

### 14.1 Review corrections applied to this report

| Issue | Correction | Where |
|---|---|---|
| E1 | "No Critical or High *defect*"; F1 is a process blocker | §1, §11 |
| E2 | F1 is the one §4.2 item that blocks M1 | §1 |
| E3 | 31 = 30 open + 1 accepted temporarily (B2-L2, ADR-021 D7) | §1, §4.2, totals |
| E4 | Batch 9 dated 2026-11-17 | §3, C9 |
| E5 | C12 and C14 dated 2026-11-17 (Batches 9–10) | §5 |
| E6 | Owner acceptance is recorded in the review prompts, not in repository history | §3, §12 |
| E7 | A2 rewritten: AI review is supporting evidence (D1); the owner's final check is still required; the Batch 14 overstatement noted | §1, §4.4 |
| E8 | F12 needs an owner decision; cross-referenced to B13-L1 | §4.3 |
| E9 | No scoring method exists; ratings labelled inconsistent pending owner clarification; R1/R3/R9 named | §9 |
| E10 | 23 rows because C8 is split into C8a/C8b | §5 |
| Counts | Informational: 100 raw / 94 unique; duplicates cross-referenced, not merged | §4.3 |
| Omissions | Batch 1 questions, concern B1-C1 and suggestion B1-S1 added; Q5 resolved by F5 | §4.5 |

### 14.2 Documentation findings fixed under D2

| Finding | File | Change |
|---|---|---|
| B8-L1 (partly) | `packages/api-types/README.md` | States it is an empty placeholder; types will be generated in M2. `package.json:5` (manifest) **left open** |
| B9-L1 | `infrastructure/README.md:23` | `core.autocrlf` is `true` at system level; `.gitattributes` `eol=lf` takes precedence |
| B12-L2, F14 | `docs/architecture/SYSTEM_ARCHITECTURE.md:39,44,166`; `docs/api/API_CONVENTIONS.md:119`; `docs/database/DATABASE_STRATEGY.md:63` | Placeholder status, "M2, not implemented yet" for types and the drift check; `IndustrySeeder` planned for M3, not created |
| B12-L3 | `docs/architecture/SYSTEM_ARCHITECTURE.md:12` | Diagram cites "Sanctum SPA cookie, ADR-019" |
| B12-L4, B13-I2 | `docs/security/DEPENDENCY_AUDIT.md`; `docs/architecture/ROADMAP.md` (U17 text) | 2026-11-17 figures added; the 2026-11-11 figures are kept as the historical record; U17 status unchanged |
| B13-L2 | `docs/architecture/ROADMAP.md:28` (U5 text) | Cites ADR-019's cookie flow and its shared-domain condition; U5 status unchanged |
| F15 (= B8-I1) | `packages/shared-config/README.md` | The example names `tsconfig.app.json`/`tsconfig.node.json`; explains that `apps/admin/tsconfig.json` only references them |

**Not edited:**
- **ADR-004 (lines 63, 67):** ADRs are never rewritten, and it already carries a "refined by ADR-019" note.
- **`apps/mobile/README.md:28` and `infrastructure/README.md:46`:** both sit under "Plan"/"Planned contents" and are not factually wrong.
- **`ROADMAP.md:49` (A4):** not in the D2 list.

### 14.3 Integrity after remediation

Exact SHA-256 values, copied from the hash snapshots taken during the D2 remediation on 2026-11-18. "Before" is the snapshot taken immediately before the first remediation edit; "after" is the snapshot taken after the last one. A later diff review rebuilt all nine "before" versions from the recorded edits, and each matched these values.

| File | SHA-256 before D2 remediation | SHA-256 after D2 remediation |
|---|---|---|
| `docs/architecture/M1_SIGNOFF_ASSESSMENT.md` | `78ec3eccacd4ed4461c6d3b635a6ac6a54f172a4b2ec7b80aeec01bb0a6ef06b` | `50ca2071d3bae3e5df964201b2e58b2521821309020108f67f995690fe55ebf9` |
| `docs/architecture/SYSTEM_ARCHITECTURE.md` | `99067e70c7daee630a5bf900f0628ec9e69e83e2994b798d145be3ab9f14d48c` | `ba5bfa17c6b1a410db72d29119bfe925f11e01e5b20c1e62689c8839dc5bf4eb` |
| `docs/api/API_CONVENTIONS.md` | `47afef09608d07c3274c739d4595e9e91bdf3e8c02566f0c1d1e8aab960426da` | `2fd66364759a8df91c34e00fe19ed6dd82bad66b636f645ee569bf9658522596` |
| `docs/database/DATABASE_STRATEGY.md` | `ac99301636dd80d21141075c2af40e52de647ad4ec32da0b8cd481367c352a9b` | `8b0dc1cff2286acec4cbf58c0ca3bdd5554face586f2df595fe64818cfd2530f` |
| `docs/security/DEPENDENCY_AUDIT.md` | `022f61a416801964b02eb38dedc1ffd372b8d32e81b0e5618eb09369fdb59a72` | `779f0fc512e6576f354bdf2c81c0f8e9d3d3c89c6f81e5378bffee99d5cd7f62` |
| `docs/architecture/ROADMAP.md` | `28afc9402cbbbd554b76660a52a7cc47485037f38f681a991ecb262d7092aa66` | `9ff380895b168dcd08967e47250b34c308ebc53b8b5ca2f71a500f9e18bc63d3` |
| `infrastructure/README.md` | `d9d896add6f068f8ed71e0c419a6cdaa068c5b5e6dc16ca0f444b3c466be8c09` | `2fd4789b3516fc7fa5a63c91a15576a0fcef70961fa1c9cc50f56d0ea259733e` |
| `packages/api-types/README.md` | `34d4ecf51c3f6457d47209d4de20aff290a8d8e85b731444ecb19c36b220d1d8` | `5fb49f15e224071cfe99198ea05c42a020487cf9fccaa71e7845c98d007f1504` |
| `packages/shared-config/README.md` | `b32506989482b4cf8814e78e19d0068ad9c90a2519c9028b4bba95913453489b` | `053a8d59efc853c205ff2d932369e986b738fef5ce9688ec1ce21f193f64e221` |

Exactly these nine files changed in the D2 remediation: this report plus 8 of the 130 reviewed inventory files. The other 122 inventory files were unchanged, and no file was created. Git stayed at 15 untracked entries, 0 staged, 0 commits and 0 remotes. All nine files were LF only, with no BOM and a final newline.

### 14.4 Follow-up corrections 3a–3e (2026-11-18)

After a read-only diff review of the D2 remediation (verdict: ACCEPT WITH CORRECTIONS), the owner approved five more documentation corrections:

| ID | File | Correction |
|---|---|---|
| 3a | `docs/security/DEPENDENCY_AUDIT.md` | The `deepmerge` sentence now says only "The cause was not determined", without naming a presumed cause |
| 3b | `docs/architecture/ROADMAP.md` (U5 text) | "chosen in ADR-019" became "proposed in ADR-019 (ADR status: Proposed)". U5 and ADR-019 statuses unchanged |
| 3c | `docs/api/API_CONVENTIONS.md:118`, `docs/architecture/SYSTEM_ARCHITECTURE.md:126` | OpenAPI generation stated as planned for M2 and not implemented yet |
| 3d | This report, §4.2 | Original as-assessed columns restored verbatim for all 31 rows (they had been overwritten for B8-L1, B9-L1, B12-L2, B12-L3, B12-L4 and B13-L2); later outcomes moved to a separate "Post-review" column |
| 3e | This report, §14.3 | Exact remediation hashes recorded above |

SHA-256 after these corrections:

| File | SHA-256 after corrections 3a–3c |
|---|---|
| `docs/security/DEPENDENCY_AUDIT.md` | `65a608d3b57cdf38cf94ff4b11a5eb0f0ee5cdce13283b8dcd2ab84ecc7cb8f3` |
| `docs/architecture/ROADMAP.md` | `254fca1aac5a9ff77559e4ac80b945ec34042dacfcf51147db2fe25bad230d0b` |
| `docs/api/API_CONVENTIONS.md` | `a98d397059102abc33510736d08676165d3e9b0cc08199109a7cfadfbf035dcf` |
| `docs/architecture/SYSTEM_ARCHITECTURE.md` | `66b1bcde07e2564e798486b2ddac8b164f84d445978abcf69acd74746682ab58` |

This report's own final hash cannot be written inside the report, because recording it would change it. It is given in the correction report delivered to the owner. No finding status, owner decision, U-item, ADR or milestone status changed, and the verdict remains **NOT READY FOR M1 FINAL SIGN-OFF**.

## 15. Post-assessment evidence: A1 full Pest run on MySQL (2026-11-19)

**Result: PASS. A1 is closed, and C15 has passing evidence.** The owner separately authorized this run: starting Laragon MySQL if needed, `config:clear` as part of `composer test`, a connection to `nobta_testing`, read-only verification, and the existing test command. Migrations, schema changes, destructive operations and access to `nobta` were excluded.

### 15.1 Environment and target

| Item | Evidence |
|---|---|
| MySQL | 8.4.3 (Laragon). It was **not running** beforehand; the existing `mysqld.exe` was started with its own `my.ini` (no configuration change) and left running |
| PHP | 8.3.30 (Laragon) |
| Effective target | Driver `mysql`, host `127.0.0.1`, port 3306, database `nobta_testing`; `DB_URL` empty; no unix socket; no read/write sections; no cached configuration; no `DB_*`/`APP_ENV` shell overrides. Reproduced with `phpunit.xml`'s forced variables. Credentials not recorded |
| Guard | `Tests\Support\TestDatabaseGuard` accepted the effective configuration and the live connection |
| Live check | `SELECT DATABASE()` returned `nobta_testing`. The verification sessions ran with `SET SESSION TRANSACTION READ ONLY` |

### 15.2 Pre-run database verification (read-only)

- **Migrations:** all four recorded as applied in batch 1, matching the four migration files exactly:
  - `0001_01_01_000000_create_users_table`
  - `0001_01_01_000001_create_cache_table`
  - `0001_01_01_000002_create_jobs_table`
  - `2026_11_05_145939_create_personal_access_tokens_table`
- **Tables:** all 10 expected tables present: `cache`, `cache_locks`, `failed_jobs`, `job_batches`, `jobs`, `migrations`, `password_reset_tokens`, `personal_access_tokens`, `sessions`, `users`.
  - All `utf8mb4_unicode_ci`, which is consistent with U21.
  - All empty except `migrations` (4 rows).
  - No demo user (`test@example.com`) present.
- **Test setup:** the three MySQL-backed test files (`DatabaseConnectionTest`, `SanctumTokenExpiryTest`, `SeederEnvironmentTest`) use `DatabaseTransactions` and run no DDL. The other test files write no data.

### 15.3 Test run

| Item | Value |
|---|---|
| Command | `composer.phar test`, run from `backend/` with Laragon PHP. It executes `php artisan config:clear` ("Configuration cache cleared successfully"; no cache existed), then `php artisan test` |
| Execution window | 2026-11-19 13:20:30–13:21:50 UTC (about 80 s for the whole command) |
| Exit code | **0** |
| Result | **94 passed, 165 assertions**; 11 suites passed |
| Failures / errors | 0 / 0 |
| Skipped, risky, incomplete, deprecated, to-do | 0 |
| Pest-reported duration | 58.15 s |

The suites include `DatabaseConnectionTest` (4), `SanctumTokenExpiryTest` (5) and `SeederEnvironmentTest` (6), none of which had run since 2026-11-11, and `TestDatabaseGuardWiringTest` (7) against the live MySQL server.

### 15.4 Post-run verification

- **Unchanged:** the same read-only check before and after the run produced identical migration records, exact row counts, `CHECKSUM TABLE` values, schema fingerprints (60 columns, 21 index entries, 0 foreign keys), demo-user count and effective configuration. The transaction-wrapped test writes were rolled back.
- **AUTO_INCREMENT counters moved:** `users` 24 → 31 and `personal_access_tokens` 16 → 21. InnoDB doesn't roll counters back, so this is expected with `DatabaseTransactions`; both tables stayed empty.
- **`jobs` and `failed_jobs`:** their AUTO_INCREMENT went from NULL to 1. This is an information_schema statistics artifact after the server start; both tables stayed empty with checksum 0.
- **Project files:** none changed by the run. All 131 inventory files were unchanged, and no file in the project was newer than the start marker.
- **Git:** 15 untracked entries, 0 staged, 0 commits, 0 remotes, before and after.

### 15.5 Limits of this evidence

- **It is a local run, not CI.** C17 (GitHub CI execution evidence) and F1 stay **BLOCKED / open**.
- **No connection to `nobta`.** None was made by the verification scripts. The suite's only reference to `nobta` is a guard test that expects a refusal; it passed, which shows the guard throws before any connection is made, because Laravel opens connections lazily. **This was not independently confirmed through MySQL server logs.**
- **Still open and unchanged:** C18 (A2), C19, A2–A4, B4-L1, the Batch 1 questions and every other open item. **M1 Final Sign-off remains PENDING**, the verdict remains **NOT READY FOR M1 FINAL SIGN-OFF**, and M2 is not authorized.

## 16. Post-remediation: C19 documentation remediation (2026-11-19)

After a read-only C19 reassessment (verdict: C19 STILL OPEN), the owner explicitly approved five corrections and the D3/U1 deferral classifications. The list in §15.5 was correct when written; C19 and A4 are now closed as recorded here.

### 16.1 Corrections applied

| Item | File | Before | After |
|---|---|---|---|
| A4 | `docs/architecture/ROADMAP.md:49` | "…GitHub Actions has not run yet, and the human review of all 130 project files is not complete." | "…GitHub Actions has not run yet. The 130/130 file review is complete and was accepted by the owner as detailed review evidence on 2026-11-18 (D1); the owner's final check of the exact staged file list and the secret-scan summary is still required before the first commit (M1_SIGNOFF_ASSESSMENT A2)." The rest of the row is unchanged: PENDING and U17 |
| B8-L1 (remainder) | `packages/api-types/package.json:5` | `"TypeScript types generated from the NOBTA OpenAPI specification."` | `"Placeholder for TypeScript types to be generated from the NOBTA OpenAPI specification (planned for M2, ADR-020)."` Only the `description` field changed; `package-lock.json` holds no description for this workspace and is unchanged |
| N1 (new, found in the C19 reassessment) | `docs/architecture/SYSTEM_ARCHITECTURE.md:78` | "(ADR-019, which supersedes the earlier "cookie or short-lived tokens" option)" | "(proposed in ADR-019, which refines ADR-004's earlier "cookie or short-lived tokens" option; ADR status: Proposed)". This matches ADR-019's own "Refines ADR-004/005" and its Proposed status |
| B13-L1(a) | `docs/architecture/ARCHITECTURE_DECISIONS.md:6` | "All ADRs below are **Proposed** (M0). They become Accepted…" | "All ADRs below are **Proposed** (M0) unless their own **Status** line says otherwise (ADR-021 is Accepted). They become Accepted…". This describes the existing statuses; **no ADR status changed** and no ADR body was edited |
| Record | This report | — | Post-remediation notes in §1 header, §4.2 (post-review column only), §4.3, §4.4 (A4), §5 (C19 and tally), §7.2, §11 and §13, plus this section |

### 16.2 Owner-approved classifications (no decision changed)

- **B13-L1(b) / F12:** which ADRs become Accepted stays an owner decision. Under D3 it is an **M2 item**, not an M1 C19 failure.
- **B13-L4:** ADR-002's module-boundary arch tests stay an owner timing decision. Under D3 it is an **M2 item**. Generic `arch()` tests already exist and pass.
- **F13:** Gulf/KWD examples. The market is still undecided (U1, needed by M2), so this is an **M2 item**. The wording is illustrative.

D3 and U1 themselves are unchanged and still open for decision before M2.

### 16.3 Resulting status

- **C19: PASS** (post-remediation). Every objectively correctable C19 item is fixed; the remaining items are owner-classified M2 items. The final M1 assessment confirms it.
- **Tally as of 2026-11-19:** 17 PASS, 0 FAIL, 2 BLOCKED (C17, C18), 1 ACCEPTED RISK, 3 DEFERRED. The as-assessed counts in §5 are kept.
- **Still open:** F1/C17 (GitHub CI never run; commit and push need separate approvals), A2/C18 (the owner's final staged-list and secret-scan check), A3, B4-L1, the Batch 1 questions and the other open findings.
- **M1 Final Sign-off remains PENDING.** The verdict remains **NOT READY FOR M1 FINAL SIGN-OFF**, and M2 is not authorized.

### 16.4 Integrity

| File | SHA-256 before | SHA-256 after |
|---|---|---|
| `docs/architecture/ROADMAP.md` | `f7acb5d10729867bfeb2ab986413f5add8eef9cda43da7e9b9d35f5355ec9525` | `b62d409967b58c5c196b0fa7e773507fdadfa596b1486e7ec1ac5e104c071e2d` |
| `packages/api-types/package.json` | `60a487e91771eee3a7adef3dcc0896a8e29750f9042eaad6b76300646b2b24c2` | `d96c2c266781bddd85e434488b90ae7aaa5e5cc732c6a3d024ead66d0cbd97fc` |
| `docs/architecture/SYSTEM_ARCHITECTURE.md` | `66b1bcde07e2564e798486b2ddac8b164f84d445978abcf69acd74746682ab58` | `67e223efcbc8b988330fc323b0eb5762e5718c78a2d192ef33a81231de6c2ff2` |
| `docs/architecture/ARCHITECTURE_DECISIONS.md` | `6fbd64e5e886932f3f366cbcb887b20b4a0b7e03c399f4fa8dca89f0cfd02f2a` | `ada5bb2112c43d180831a9c3bc9fca7cfdd2704a5b5aa648bf4325a853296372` |
| This report | `ef9d5022421a881e8c73a7214082d596df17b0e4809a208a61a8ceac82d3ca31` | Can't be recorded inside itself; given in the remediation report to the owner |

No database, MySQL, test, build, audit, install, cache or Git operation was performed. Git stayed at 15 untracked entries, 0 staged, 0 commits and 0 remotes.
