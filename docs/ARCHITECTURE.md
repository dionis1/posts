# Application decisions

## One editorial team

Roles are global within one workspace. Query scopes and policies restrict authors to their own drafts; reviewers and editors have explicit additional capabilities. This is not a multi-tenant application.

## Action classes hold mutations

Create, update, transition, restore, delete, and scheduled publication are separate invokable actions. They share protected writing primitives through PostAction: validation, version checks, revision snapshots, auditing, and notification delivery. HTTP controllers and Livewire call concrete actions; the existing PostService interface remains a small compatibility facade for older consumers and regression tests.

Comment moderation, media writes, role assignment, and taxonomy creation also have dedicated actions. Transactions keep related database writes together. Uploaded files are removed if database persistence fails; deletion schedules filesystem cleanup after commit.

## One set of content rules

PostContentRules supplies constraints to the HTTP request, API request, Livewire Form object, and action layer. Domain actions validate too because HTTP is not their only caller. Client-provided user IDs, statuses, and versions are never accepted as content fields. Output escaping and safe Markdown rendering complement validation; validation is not a universal sanitizer.

## Thin Livewire components

PostForm owns editable fields, normalization, and validation. PostEditor owns locked identity/version, authorization, events, and action orchestration. Computed values are cached for the current request, not globally across users. Neither a visible button nor a locked ID replaces server authorization.

PostLibrary binds search with `wire:model.live.debounce.300ms`, reflects filters in the URL, resets pagination on filter changes, and queries at most 12 records per page with explicit eager loading. The dashboard aggregation runs once rather than in both a controller and component.

## Transactions and concurrency

Every content mutation writes its version, revision, and activity together. Version predicates prevent lost updates even when a timestamp has insufficient precision. The schedule job rechecks status, version, and due time; duplicate, early, cancelled, and stale jobs safely do nothing. Approval is tied to a content version: edits invalidate previous approval. Tests prove self-approval denial and rollback when auditing fails.

## Performance choices

SQLite FTS5 indexes title/body and uses triggers to track changes. Composite indexes support ownership/status/category and update ordering; other relationship indexes support joins and moderation. Eager loading is explicit, and development/test lazy loading is prohibited. Indexes are query-specific choices, not a substitute for measuring a future workload.

## Verification strategy

Existing PHPUnit workflow tests remain valuable and run alongside Pest. New Pest tests exercise Livewire state, validation, events, forged identity, stale drafts, permissions changing after mount, query counts, two-factor replay/expiry, and media handling. Browser tests use a separate disposable container and database. No personal data is seeded or reset by tests.
