# Posts Studio — Laravel Editorial Platform

A Laravel 13 editorial publishing application, modernized from an earlier Laravel 6 posts demo. This repository focuses on **Laravel application development**, not infrastructure or deployment automation.

## Implemented areas

- Account registration, login, email verification, password resets, profiles, and optional two-factor authentication.
- Author, reviewer, editor, and administrator roles, with policy-based separation of duties.
- Draft → review → approval → scheduled/published → archived workflows.
- Post revision snapshots, activity history, and optimistic concurrency checks.
- Categories, tags, Markdown content with raw-HTML and unsafe-link protections, and SEO metadata.
- Published article pages, sitemap, moderated comments, media upload/processing, notifications, CSV analytics, and a versioned Sanctum API.
- SQLite full-text search with triggers that keep its index synchronized.
- PHPUnit feature tests and development-only GitHub Actions checks (tests, Pint, Vite build).

Some features have more test coverage than others. A passing workflow is **not** a substitute for a manual review of every user journey or a production security assessment.

## Requirements

- **PHP 8.4+** (the committed Composer lockfile includes Symfony packages requiring PHP 8.4.1+).
- Composer 2.
- Node.js 22 and npm.
- PHP extensions required by Composer and SQLite, plus GD for image processing.

## Local development

```bash
git clone git@github.com:dionis1/posts.git
cd posts
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm ci
npm run build
php artisan serve
```

Open [http://localhost:8000](http://localhost:8000). Register as an author; new users must verify their email before opening the editorial workspace. In the example environment, outgoing email is written to `storage/logs/laravel.log`, not sent to real inboxes.

Create a local administrator interactively, using an email you control:

```bash
php artisan posts:admin admin@example.test
```

The command prompts for credentials when the account does not exist. It can also promote an existing account with confirmation. No default administrator credentials are stored in the repository.

## Queues and scheduled publishing

Workflow notifications, media processing, and scheduled publishing use Laravel jobs. In separate local terminals, run:

```bash
php artisan queue:work
php artisan schedule:work
```

The scheduler checks for due articles every minute. The publication job rechecks the post status and optimistic-lock version to guard against stale or duplicate delivery.

## Development checks

```bash
php artisan test
vendor/bin/pint --test
npm ci
npm run build
composer audit
npm audit
```

### Browser smoke tests

Playwright checks public reading, registration, email-verification gating, language switching, login, creating a draft, and Markdown preview in Chromium. GitHub Actions automatically prepares a disposable SQLite database and a **test-only verified editor account** for these checks. The test account is not part of the ordinary application database.

To run the same browser checks locally without touching your development database, first create a separate test environment and SQLite database, then serve the app on port 8010 with the built Vite assets. Install Chromium with `npx playwright install chromium`, and run `npm run test:browser` in a separate terminal. Do not run browser tests against a database containing real user data.


GitHub Actions runs the PHPUnit suite, Pint, and Vite build on pushes and pull requests. All tests use an isolated in-memory SQLite database, with Vite disabled for PHP feature testing.

## API

The editorial API is under `/api/v1/posts`, protected by Sanctum authentication, email verification, and a named rate limiter. Endpoints check `read` or `write` token abilities as well as per-post authorization policies. Existing feature tests cover token restrictions, account isolation, tags, and SEO metadata.

## Project structure

- `app/Services/PostService.php`: content validation, state transitions, revisions, auditing, and version-aware mutations.
- `app/Policies`: post, media, and comment permissions.
- `app/Http/Controllers`: HTTP and JSON API endpoints.
- `app/Jobs`: media processing and scheduled publication.
- `database/migrations`: legacy-data modernization and the publishing schema.
- `tests/Feature`: business workflows, HTTP access, and API regressions.
- `resources/views`: editorial and public-facing pages.

## Development notes and limitations

- This is a **portfolio/development application**, not an audited production service.
- Publishing email is log-only until a real mail provider is configured.
- A local `.env` file contains environment-specific secrets and must never be committed.
- Local data should be backed up before applying schema changes to an existing database.
- Review authorization, uploads, queue failure handling, accessibility, and restoration procedures before a public deployment.
- No infrastructure-as-code or deployment pipeline is included by design.

## License

See the repository's existing license and attribution information.
