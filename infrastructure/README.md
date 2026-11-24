# infrastructure

Local development setup, CI and deployment configuration.

**Status (M1):**
- The CI workflow is at [.github/workflows/ci.yml](../.github/workflows/ci.yml). It hasn't run yet because the repo has no remote.
- Local MySQL databases and user are created.
- A `NOBTA_API_35` Android Virtual Device is created.

## Local development (Windows, verified in M0)

Laragon (`C:\laragon`) provides PHP 8.3.30, Composer 2.9.4, MySQL 8.4.3, Redis 5.0.14 and Mailpit.

Known caveats found during the M0 audit:

1. **`composer` and `mysql` aren't on the system PATH.** They only resolve inside the Laragon terminal. Either use the Laragon terminal, or add these to the user PATH:
   - `C:\laragon\bin\composer`
   - `C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin`
2. **`ANDROID_HOME` isn't set**, and `adb` isn't on PATH, even though the SDK is installed at `%LOCALAPPDATA%\Android\Sdk`. Set:
   - `ANDROID_HOME=%LOCALAPPDATA%\Android\Sdk`
   - add `%ANDROID_HOME%\platform-tools` and `%ANDROID_HOME%\emulator` to PATH
3. **Laragon's Redis is 5.0.14**, an old unofficial Windows port. Don't depend on it. Local queues use the `database` driver (see ADR-009). Production Redis is chosen separately.
4. **Git `core.autocrlf` is `true` at system level** (`C:\Program Files\Git\etc\gitconfig`, the Git for Windows default). The repo's `.gitattributes` (`* text=auto eol=lf`) takes precedence for text files, so they are stored and checked out with LF.
5. At the M0 audit the only Android Virtual Device was `SignagePlayer` (another project). The dedicated NOBTA AVD `NOBTA_API_35` was created in M1 and already exists (see "Android emulator" below); `SignagePlayer` is left untouched.

## Databases (local)

| Purpose | Name |
|---|---|
| Development | `nobta` |
| Automated tests | `nobta_testing` |

Both were created in M1 (`CREATE DATABASE IF NOT EXISTS`, `utf8mb4` / `utf8mb4_0900_ai_ci`). The MySQL user `'nobta'@'localhost'` has `ALL PRIVILEGES` on `nobta.*` and `nobta_testing.*` only, with no global privileges. Its password lives only in `backend/.env`, which Git ignores. To set up another machine, create the same user with your own password and put it in your local `backend/.env`.

## Android emulator

- AVD: `NOBTA_API_35` (Pixel 7 profile, `system-images;android-35;google_apis;x86_64`), with WHPX acceleration verified. It already exists; do not recreate it.
- Start it with `emulator -avd NOBTA_API_35`.
- Install the EAS development build APK on it (`adb install -r <apk>`), then run Metro with `npm run start -w @nobta/mobile` and open the app. This is the path verified in M1 (ROADMAP, Stage H evidence).
- `npm run android -w @nobta/mobile` (a local Gradle build) is currently blocked on Windows by the ninja path-length limit (ROADMAP U16).

## Planned contents

```
infrastructure/
├── ci/          # CI pipeline definitions (lint, typecheck, tests, OpenAPI drift check)
├── docker/      # optional containerized dev/prod images (Docker is installed; not required locally)
└── deploy/      # environment and deployment notes
```
