# stackr — progress

A Trello clone: projects, each with its own board of lists and cards.

_Last updated: 2026-10-24_

## Where we are

| Step                                                         | Status  |
| ------------------------------------------------------------ | ------- |
| 1. Data model (projects, members, lists, cards)              | ✅ done |
| 2. Projects overview (create, rename, delete, sidebar)       | ✅ done |
| 3. Board (lists + cards, drag & drop)                        | ✅ done |
| 4. Branding (name, logo, indigo theme, login screen)         | ✅ done |
| 5. Card details popup (title, description, due date, delete) | ⏭ next  |

Everything is committed on `main` and pushed to `origin`
(https://github.com/marceli-to/stackr.web).

## Stack

- Laravel 13 + Inertia 3 + Vue 3 (TypeScript), from the official Vue starter kit (Fortify auth)
- Tailwind 4, shadcn-vue components in `resources/js/components/ui`
- Wayfinder for typed routes (`resources/js/actions`, `resources/js/routes` are generated)
- `vue-draggable-plus` (SortableJS) for drag & drop
- Pest, PHPStan (level 7), Pint, vue-tsc, `vp check` for lint/format
- SQLite locally. The position columns are also set up correctly for MySQL and Postgres.

## Decisions made

- **No realtime.** Reverb and Pusher were set up, then removed at the user's request. Changes by
  other people show up after a reload, and the last write wins. Production is likely shared hosting,
  so nothing may rely on a long-running process.
- **A project is a board.** There's no workspace level above it. You can have as many projects as you like.
- **Lists are called `Stack` in code**, because PHP doesn't allow a class named `List`. The UI says "list".
- **Roles:** `owner` or `member`, stored on `project_user` (`ProjectMembership` pivot,
  `ProjectRole` enum). Members can view and edit the project and its board; only the owner can delete it.
  Deleting a user deletes the projects they own.
- **Order uses fractional indexing.** `app/Support/FractionalIndex.php` is a PHP port of
  rocicorp/fractional-indexing. It matched the JS original on 20,000 random insertions. The server
  computes the keys: the client sends `{ index, stack_id? }`, and the server places the item among its
  siblings. Position columns use a byte-by-byte collation (`C` / `utf8mb4_bin`).
- **The screen updates first.** The board moves the item straight away and saves in the background
  (`resources/js/components/board/useBoardMove.ts`, `async` Inertia visits). Once all moves have
  finished it takes the server's order again, which also undoes a rejected move.
- **Branding:** the name is "Stackr". The logo is three offset bars (a stack of cards) in
  `AppLogoIcon.vue`; the favicons in `public/` use the same mark, white on an indigo rounded square.
  Indigo is `--primary` in light and dark mode (`resources/css/app.css`); everything else stays neutral.
  The starter kit's Repository/Documentation links are gone.
- **Theme:** light indigo (`indigo-50`) behind everything — sidebar, auth pages, board lists — with
  white surfaces on top using the shared `shadow-soft` utility (`app.css`): the content panel, the
  login card, project cards. Ghost buttons inside lists hover in `indigo-100`. Dark mode stays neutral.
- **Sidebar:** logo (links to projects, no hover background), "Your projects", and the user menu. The
  user menu shows the name only — no avatar/initials; collapsed, it shows a person icon.
- **Project page** shows just the heading and board. Editing and deleting a project happens from the ⋮
  menu on the projects overview.
- **No landing page:** `/` sends guests to the login and signed-in users to `/projects`.
- **Favicons** are linked as SVG + PNG (`?v=2`), not `.ico`: Herd answers `/favicon.ico` with a 404
  status, which browsers ignore. `favicon.ico` still exists for direct requests.
- **Auth pages** use `AuthCardLayout.vue`: a white card on `bg-indigo-50` (neutral in dark mode).
  The login has no "Remember me"; instead sessions last 12 hours (`SESSION_LIFETIME=720`, set it in
  production's `.env` too, along with `APP_NAME=Stackr`).
- `/dashboard` redirects to `/projects`, so Fortify's home setting and the old links keep working.

## Where things are

- Backend: `app/Http/Controllers/{Project,Stack,Card}Controller.php`, `app/Policies/ProjectPolicy.php`,
  `app/Models/{Project,ProjectMembership,Stack,Card}.php`, `routes/web.php`
- Frontend: `resources/js/pages/projects/{Index,Show}.vue`, `resources/js/components/board/*`,
  `resources/js/components/projects/*`, `resources/js/components/NavProjects.vue`
- Tests: `tests/Feature/{ProjectTest,BoardTest,DashboardTest,HomeTest}.php`, `tests/Unit/FractionalIndexTest.php`

## Running it

- Herd serves it at https://stackr.web.test (the Webroot folder is parked in Herd; secured with
  `herd secure stackr.web`, `APP_URL` is https).
- `npm run dev` while you work. Without it, the site uses the last `npm run build`.
- Checks: `php artisan test`, `vendor/bin/phpstan analyse --memory-limit=1G`, `vendor/bin/pint --test`,
  `npm run types:check`, `npm run check`
- Account: `m@marceli.to`, email verified by hand in the database.

## Next up

1. **Card details popup:** click a card to edit its title and description, set a due date, or delete it.
   The `cards` table already has `description` and `due_at`.
2. Show a short message instead of Inertia's full-page 403 overlay when a move is rejected.
3. Playwright tests for drag & drop. The one-off check in `/tmp` was thrown away; proper tests need
   their own database and `.env`.
4. Later: invite members to a project (the data model already supports it), labels, archiving, search.
   The header layout's search button is a starter-kit leftover that does nothing yet.
5. Production: certificate from the host, and set `APP_URL` (https), `APP_NAME=Stackr` and
   `SESSION_LIFETIME=720` in its `.env`.
