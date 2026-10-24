# stackr — progress

A Trello clone: projects, each with its own board of lists and cards.

_Last updated: 2026-10-24_

## Where we are

| Step                                                   | Status  |
| ------------------------------------------------------ | ------- |
| 1. Data model (projects, members, lists, cards)        | ✅ done |
| 2. Projects overview (create, rename, delete)          | ✅ done |
| 3. Board (lists + cards, drag & drop)                  | ✅ done |
| 4. Branding (name, logo, login screen)                 | ✅ done |
| 5. Design: "Field" (see below)                         | ✅ done |
| 6. Card details (title, description, dates, labels, …) | ⏭ next  |

Code lives on GitHub: https://github.com/marceli-to/stackr.web

## Stack

- Laravel 13 + Inertia 3 + Vue 3 (TypeScript), from the official Vue starter kit (Fortify auth)
- Tailwind 4, shadcn-vue components in `resources/js/components/ui` (restyled), Phosphor icons
  (`@phosphor-icons/vue`, bold by default via `app.provide('weight', 'bold')` in `app.ts`)
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
- **Design "Field"** (chosen out of three directions explored with the Hallmark skill). Every project
  stands on its own dark colour (cobalt, rust, moss, oxblood, lagoon, plum: `ProjectColor` enum,
  `projects.color`); the board page, top bar included, is drenched in it, lists sit in a darker well
  of it, cards are plain white. Everything else is neutral warm paper with ink.
    - Tokens in `resources/css/tokens.css`, mapped onto shadcn's variables in `app.css`. Fields are
      `[data-field="<colour>"]`, which set `--field`, `--field-well`, `--field-ink` (always white) and
      the focus ring.
    - Type: Antonio 700 (poster heads: `--text-poster`, board title `--text-board-title`, list names,
      dialog titles, the wordmark) and Hanken Grotesk 400/600 for everything else, loaded via Bunny in
      `vite.config.ts`.
    - Shell: a slim top bar (logo → projects, the user's initials → menu). No sidebar, no breadcrumbs,
      no project switcher.
    - Overview: a wall of colour tiles that ends with a neutral "New project" tile.
    - Controls: 4px corners, ink primary buttons, white inputs with an ink hairline (`--control-edge`),
      white menus/dialogs/toasts with `--surface-edge` and `--float-shadow`, dark-grey focus ring.
      Focus outlines need `focus-visible:outline-solid` (Tailwind 4's `outline-none` clears the style).
- **Logo:** three offset bars (a stack of cards) in `AppLogoIcon.vue`; favicons in `public/` are the
  same mark on an ink tile.
- **Cards** open in a dialog from the board; the URL carries `?card=<id>`, so a card can be linked
  and the browser's back button closes it. Opening reloads only the `card` prop. A card has:
  title, Markdown description (rendered on the server with `Str::markdown`, raw HTML stripped,
  unsafe links dropped), start and due dates plus a "complete" tick, labels (per project, 8 colours,
  `LabelColor`), members (project members), checklists with items, attachments and comments
  (authors edit/delete their own). Shaping lives in `app/Support/CardData.php` (`face()` for the
  board, `detail()` for the dialog).
- **Attachments** are stored privately on the `local` disk under `attachments/{project}/{card}/` and
  served by `AttachmentController@show` to project members only (no `storage:link`, so shared
  hosting works). Up to 20 files of 20 MB per upload; drop files on the open card, paste an image,
  or use the file picker. The first image becomes the cover; any image can be made the cover.
  Deleting a card, a list or a project removes its files (model events).
- **Projects** have a name, an optional description, an optional link (`url`, http/https) shown
  on the board, and a colour. Editing and deleting happens from the ⋮ menu on the overview tiles.
- **No landing page:** `/` sends guests to the login and signed-in users to `/projects`.
- **Favicons** are linked as SVG + PNG (`?v=3`), not `.ico`: Herd answers `/favicon.ico` with a 404
  status, which browsers ignore. `favicon.ico` still exists for direct requests.
- **Auth pages** use `AuthCardLayout.vue`: a centred white card with the logo, title and fields.
  Passkeys can be managed in Security settings but are not offered on the login screen.
  The login has no "Remember me"; instead sessions last 12 hours (`SESSION_LIFETIME=720`, set it in
  production's `.env` too, along with `APP_NAME=Stackr`).
- `/dashboard` redirects to `/projects`, so Fortify's home setting and the old links keep working.

## Where things are

- Backend: `app/Http/Controllers/{Project,Stack,Card}Controller.php`, `app/Policies/ProjectPolicy.php`,
  `app/Models/{Project,ProjectMembership,Stack,Card}.php`, `routes/web.php`
- Card backend: `app/Http/Controllers/{Card,Label,CardLabel,CardMember,Checklist,ChecklistItem,Attachment,Comment}Controller.php`,
  `app/Models/{Label,Checklist,ChecklistItem,Attachment,Comment}.php`, `app/Support/CardData.php`
- Frontend: `resources/js/pages/projects/{Index,Show}.vue`, `resources/js/components/board/*`,
  `resources/js/components/card/*` (dialog, pickers, sections; `board.ts` holds the shared helpers),
  `resources/js/components/projects/*`, `resources/js/components/AppTopBar.vue`,
  `resources/js/layouts/{AppLayout,settings/Layout,auth/AuthCardLayout}.vue`
- Tests: `tests/Feature/{ProjectTest,BoardTest,CardDetailsTest,DashboardTest,HomeTest}.php`, `tests/Unit/FractionalIndexTest.php`

## Running it

- Herd serves it at https://stackr.web.test (the Webroot folder is parked in Herd; secured with
  `herd secure stackr.web`, `APP_URL` is https).
- `npm run dev` while you work. Without it, the site uses the last `npm run build`.
- Checks: `php artisan test`, `vendor/bin/phpstan analyse --memory-limit=1G`, `vendor/bin/pint --test`,
  `npm run types:check`, `npm run check`
- Account: `m@marceli.to`, email verified by hand in the database.
- `.hallmark/` (untracked) is the Hallmark design skill's local log.

## Next up

1. Invite members to a project (the data model supports it; card members only list people who are
   already on the project).
2. Card details, round two: move or copy a card from the dialog, archive instead of delete, an
   activity log, @mentions in comments, cover colours, image thumbnails (images are served in full
   size today).
3. Show a short message instead of Inertia's full-page 403 overlay when a move is rejected.
4. Playwright tests for drag & drop and the card dialog.
5. Production: certificate from the host, and set `APP_URL` (https), `APP_NAME=Stackr` and
   `SESSION_LIFETIME=720` in its `.env`. Make sure PHP's `upload_max_filesize` and `post_max_size`
   allow 20 MB uploads.
