# Architecture Note

## Overview

A Laravel 11 REST API for service requests with three roles (Employee, Manager, Operations). Every request follows an explicit workflow, has an owner, and keeps a full activity history.

Request flow:

```
Route -> auth:sanctum -> Form Request (authorize + validate) -> Controller -> RequestWorkflow -> API Resource
```

## Key decisions

**1. Workflow defined in one place.**
The transition map lives in the `RequestStatus` enum (`allowedTransitions()`), and `RequestWorkflow::transition()` is the only code that changes a status. Submit, decision, assign and status endpoints all call it, so no rules are scattered across controllers as `if` statements.

**2. 403 and 422 are kept separate.**
Policies answer "is this person allowed?" (role and relationship, such as owner or the requester's manager) and return 403. The workflow answers "is this status move valid?" and returns 422 through `InvalidTransitionException`. Policies never check status, so each rule is easy to test on its own.

**3. Audit trail is atomic.**
The status update and the `activity_logs` row are written inside one `DB::transaction`. A status can never change without a history entry recording who, when, from, to, and an optional comment.

**4. Notifications are queued.**
After the transaction commits, `transition()` dispatches `SendRequestStatusNotification`. The job works out the recipients and sends database notifications, so API responses never wait on notification work. It dispatches after commit so a worker can never see stale data.

**5. Enums for roles, statuses and categories.**
Valid values exist in one place and models cast them automatically, so code compares enums rather than raw strings.

**6. Data model.**
Four core tables: `users` (with `role` and a self-referencing `manager_id` for direct reports), `service_requests`, `comments` and `activity_logs`. Indexes on `status` and the foreign keys support the filtered, role-scoped list queries. The model is named `ServiceRequest` because Laravel already has a `Request` class.

**7. Performance and API quality.**
Lists eager load requester and assignee (no N+1), are paginated with `links` and `meta`, and sorting is limited to a whitelist of columns. Form Requests validate input, API Resources shape output, and all errors return JSON.

## Trade-offs

- **Single `role` column.** Simple for three fixed roles, but a user has one role and adding a role needs a code change. A roles table or Spatie Permission would be the next step.
- **No row locking.** Two simultaneous actions on the same request could race. `lockForUpdate()` inside the transition transaction would fix this.
- **Recipients computed at job run time.** If a request is reassigned before the job runs, a different person could be notified.
- **Database notifications only.** No mail server is needed, but these are not emails. Adding `mail` to `via()` and a `toMail()` method would send them.
- **Drafts are private.** Managers cannot see a request until it is submitted, which is a deliberate assumption recorded in the README.
- **Static analysis is incomplete.** Larastan runs at level 5 with remaining findings caused by Eloquent and Resource magic, listed as a next step.