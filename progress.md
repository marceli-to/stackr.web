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
| 6. Card details (title, description, dates, labels, …) | ✅ done |
| 7. Inviting members                                    | ✅ done |
| 8. Production on stackr.kimiyu.ch (see "Production")   | ✅ done |
| 9. Landing page and legal notice                       | ✅ done |
| 10. Search and filters on the board                    | ✅ done |
| 11. Archive for cards and lists                        | ✅ done |
| 12. Move or copy a card from the dialog                | ✅ done |
| 13. Activity log per card                              | ✅ done |
| 14. Notifications (bell + email) and @mentions         | ✅ done |
| 15. Colour covers                                      | ✅ done |
| 16. Image thumbnails                                   | ✅ done |
| 17. Card templates, copying checklists                 | ✅ done |
| 18. Calendar view                                      | ✅ done |
| 19. Keyboard shortcuts                                 | ✅ done |
| 20. Card limits and collapsing lists                   | ✅ done |
| 21. Board background photos                            | ✅ done |
| 22. Watching cards and boards                          | ✅ done |
| 23. Calmer board chrome (one bar, ⋯ menu)              | ✅ done |
| 24. Gradient project colours                           | ✅ done |
| 25. Project keys in URLs, join links                   | ✅ done |

Everything is on `main` and pushed to GitHub (https://github.com/marceli-to/stackr.web).
Production (https://stackr.kimiyu.ch) runs `19807cb` (deployed 2026-10-24, all migrations run).
Still open there: a manual check, see "After the deploy" under "Next up".
(`design/field` and `feature/card-details` are merged into `main` and can be deleted.)

## Stack

- Laravel 13 + Inertia 3 + Vue 3 (TypeScript), from the official Vue starter kit (Fortify auth)
- Tailwind 4, shadcn-vue components in `resources/js/components/ui` (restyled), Phosphor icons
  (`@phosphor-icons/vue`, bold by default via `app.provide('weight', 'bold')` in `app.ts`)
- Wayfinder for typed routes (`resources/js/actions`, `resources/js/routes` are generated)
- `vue-draggable-plus` (SortableJS) for drag & drop
- Pest, PHPStan (level 7), PHP-CS-Fixer, vue-tsc, `vp check` for lint/format
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
  finished it takes the server's order again, which also undoes a rejected move. A rejected or
  failed move shows one error toast instead of Inertia's full-page error overlay.
- **Design "Field"** (chosen out of three directions explored with the Hallmark skill). Every project
  stands on its own dark colour (cobalt, rust, moss, oxblood, lagoon, plum, or one of five gradients
  blending two of them: dusk, ember, tide, grove, fig; `ProjectColor` enum, `projects.color`); the
  board page, top bar included, is drenched in it, lists sit in a darker well of it, cards are plain
  white. Solid colours carry a faint diagonal sheen; the `field-fill` utility paints a field's
  `--field-fill` (sheen or gradient), and a gradient's `--field` is its two colours' midpoint. Everything else is neutral warm paper with ink.
    - Tokens in `resources/css/tokens.css`, mapped onto shadcn's variables in `app.css`. Fields are
      `[data-field="<colour>"]`, which set `--field`, `--field-well`, `--field-ink` (always white) and
      the focus ring.
    - Type: Antonio 700 (poster heads: `--text-poster`, list names, dialog titles, the wordmark and
      the board name in the top bar) and Hanken Grotesk 400/600 for everything else, loaded via Bunny in
      `vite.config.ts`.
    - Shell: a slim top bar in three columns: logo (→ projects) and, on a board, the board's name
      left; the board's search centred; member avatars and the ⋯ app menu right
      (`components/AppMenu.vue`, a tinted circle like the avatar, with a yellow dot while anything is
      unread). The user's initials (account menu, opens upwards) sit fixed in the bottom-left corner.
      On a board both menus are frosted glass in the project's colour with white ink (`menu-glass`
      utility; the menu is teleported, so it carries its own `data-field`), like the frosted search.
      The board has no toolbar row and no big title of its own (only a hidden `h1`); it teleports
      its search (`#topbar-search`) and its own `AppMenu` with the board items (`#topbar-actions`)
      into the bar. No sidebar, no breadcrumbs, no project switcher.
    - Board: lists show no card count (unless they have a card limit); the list menu (and every other ⋯ menu, e.g. on project tiles) uses horizontal dots. "Add a card" is card-height
      with a faint tint, "Add a list" has the list's well colour.
    - Overview: a wall of 16:10 colour tiles that ends with a neutral "New project" tile.
    - Controls: 4px corners, ink primary buttons, white inputs with an ink hairline (`--control-edge`),
      white menus/dialogs/toasts with `--surface-edge` and `--float-shadow`, dark-grey focus ring.
      Focus outlines need `focus-visible:outline-solid` (Tailwind 4's `outline-none` clears the style).
      Text fields don't get the outline: the `focus-field` utility (`app.css`) swaps it for a soft
      halo, `--field-focus` in `tokens.css`. Every enabled button has `cursor: pointer` (`base.css`).
    - Card dialog: no colour strip unless there's a cover; on open it focuses the sheet, not the
      close button, so no ring shows after a click. Faint `--surface-edge` hairlines give it
      structure: a full-width line under the header, `divide-y` between the main column's sections,
      a vertical line beside the sidebar (a horizontal one above it on narrow screens) and one
      between "Add to card" and "Actions".
- **Shapes:** text buttons, inputs and labels use the 4px `--radius-sheet`; tiles, dialogs, menus
  and the auth card the 6px `--radius-tile`. Icon-only buttons are round (`size="icon…"` is
  `rounded-full`), like avatars. A "+" at the end of a row takes the row's shape ("+ labels"
  stays square). The members dialog opens from a round invite button next to the avatars.
- **Logo:** three offset bars (a stack of cards) in `AppLogoIcon.vue`; favicons in `public/` are the
  same mark on an ink tile.
- **Members & invitations:** the board's top bar shows the other members as an avatar stack (your own
  initials are the account menu); "Invite people…" ("Members…" for non-owners) in the ⋯ menu. Both
  open the members dialog (`membersOpen` in `components/projects/members.ts` is shared between them) (`components/projects/ProjectMembers.vue`). Only the owner invites (by email) and
  removes members; anyone else can leave; the owner can't leave. An address with an account is added
  right away and gets `AddedToProjectMail`; any other address gets a `project_invitations` row and
  `ProjectInvitationMail` with `/invitations/{token}` (valid 14 days, inviting again renews it).
  Guests opening the link go to register (or login) with the email filled in and come back via
  `url.intended`; accepting also marks the email verified when it matches. Removing a member also
  takes them off the project's cards.
- **Join link:** besides email, the owner can turn on a shareable link (`projects.invite_token`,
  `/join/{token}`, `InviteLinkController`). Anyone opening it joins as a member (guests register
  first and come back via `url.intended`). It doesn't expire; "New link" replaces it, "Turn off"
  clears it. Only the owner sees it (`inviteLink` prop).
- **Project URLs use a random key, not the id:** `projects.key` (10 lowercase letters/digits, set on
  create) is the route key (`/projects/k3f9x2qa7m`). Old numeric URLs (`/projects/5?card=7`)
  redirect members to the key URL. The frontend passes `project.key` to Wayfinder; `id` is still in
  the resource for things like move/copy destinations. Cards still show up as `?card=<id>`.
- **Mail:** the project mails (`AddedToProjectMail`, `ProjectInvitationMail`) are queued
  (`ShouldQueue`, database queue); in production a cron job works through the queue every minute.
  Laravel's verification and password-reset mails are still sent right away. Locally, run
  `php artisan queue:work` (or `queue:listen`) to actually send queued mail. Templates are Laravel's published
  markdown mail (`resources/views/vendor/mail`, warm paper/ink colours, the logo as
  `public/mail-logo.png` because mail clients don't show SVG) plus `resources/views/mail/`.
  Locally `.env` points at Mailhog (SMTP 1025, inbox at http://127.0.0.1:8025).
- **Cards** open in a dialog from the board; the URL carries `?card=<id>`, so a card can be linked
  and the browser's back button closes it. Opening reloads only the `card` prop. A card has:
  title, Markdown description (rendered on the server with `Str::markdown`, raw HTML stripped,
  unsafe links dropped), start and due dates plus a "complete" tick, labels (per project, 8 colours,
  `LabelColor`), members (project members), checklists with items, attachments and comments
  (authors edit/delete their own). Shaping lives in `CardFaceResource` (the board) and
  `CardResource` (the dialog).
- **Attachments** are stored privately on the `local` disk under `attachments/{project}/{card}/` and
  served by `AttachmentController@show` to project members only (no `storage:link`, so shared
  hosting works). Up to 20 files of 20 MB per upload; drop files on the open card, paste an image,
  or use the file picker. The first image becomes the cover; any image can be made the cover.
  Deleting a card, a list or a project removes its files (model events).
  A card can have a colour cover instead (`cards.cover_color`, the 8 label colours): "Cover" in
  the dialog (`CardCoverPicker.vue`) picks a colour or one of the card's images; choosing one
  clears the other, and a new upload doesn't replace a colour cover.
- **Thumbnails:** images wider than 640px get a WebP copy on upload (`App\Support\Thumbnail`, GD,
  EXIF rotation honoured; GIFs, huge images over 40 MP and anything GD can't read are skipped) at
  `thumb_<name>.webp` beside the original (`attachments.thumb_path`). `attachments.thumbnail`
  serves it, or the original when there's none; covers, the dialog cover and previews use it.
  Moves and copies take thumbnails along. `app:make-thumbnails` backfills; `deploy.sh` runs it.
- **Search and filters** (`components/board/{BoardFilterBar.vue,filter.ts}`) work in the browser:
  the board already has every card. A search field (`/` focuses it, Esc clears it) matches the
  title and label names, every word has to match; an × inside the field clears it. The "Filter"
  popover is **hidden for now** (`BoardFilterBar` renders it only with `with-filters`; the logic
  is intact). It picks members ("Cards
  assigned to me", others, "No members"), labels ("No labels") and due dates (overdue, next day,
  next week, none, complete). Any choice within a group matches; every group has to. Filtered-out
  cards are hidden with `v-show`, not removed, so SortableJS still counts them and a drop saves
  the right index. The filter survives opening a card (`preserveState`) but not a reload.
- **Archive:** cards and lists have `archived_at` (`active()` scope, column qualified because
  both tables have it). Archiving keeps the position, so a restore puts things back where they
  were; moves only count active siblings (`MoveCard`, `MoveStack`), and cards can't be dropped into
  an archived list. The card dialog offers "Archive"; an archived card (or one in an archived
  list) shows a banner and "Restore" / "Delete" instead, so deleting a card takes two steps.
  The list menu has "Archive list"; deleting a list happens from the archive panel
  (`components/board/BoardArchivePanel.vue`, "Archive…" in the ⋯ menu), which loads the
  `archive` prop on open (`Inertia::optional`). Restoring a card also restores its archived list.
  Project tiles count active lists and cards only.
- **Move and copy** (`components/card/CardMoveCopy.vue`, "Move" / "Copy" in the card dialog's
  Actions): pick a board (any the user is on, from the lazy `destinations` prop), a list and a
  position. `cards.move` takes any active list on the user's boards; a card that leaves the board
  closes and shows "Card moved to …". `CopyCard` always copies title, description and dates (not
  completion or comments); checklists, labels, members and attachments are optional (files are
  copied, the cover follows). Arriving on another board, `CarryCardOver` maps labels by name and
  colour (creating missing ones) and drops members who aren't on that board; a moved card's files
  move to `attachments/{new project}/{card}/`.
- **Activity log:** `activities` (card, user, `type`, `data` json, `created_at` only). The actions
  record through `RecordActivity` (the signed-in user is the actor): created, copied, moved
  (between lists), moved_board, archived, restored, completed, reopened, due_set, due_removed,
  member_added, member_removed, attached, checklist_added, item_completed. `data` keeps names
  as they were. The dialog shows the newest 100 mixed in with the comments ("Comments and
  activity"); `describeActivity()` in `card/board.ts` writes the sentences; "Hide details" is
  remembered in localStorage.
- **Notifications** (`app/Notifications`, Laravel database notifications): `AssignedToCard`
  (someone else added you), `MentionedInComment`, `CardDueSoon`. All extend `CardNotification`:
  the database channel runs at once (`viaConnections` → `sync`), the mail goes through the queue,
  and only if `users.email_notifications` is on (profile settings, "Email me too"). "Notifications"
  in the ⋯ menu (`components/AppMenu.vue`) shows the shared `notifications.unread` count; the
  dialog (`components/NotificationsDialog.vue`) loads `notificationList` (optional shared prop,
  30 newest) when opened; a click posts to
  `notifications.open`, which marks it read and opens the card. `app:send-due-reminders` (every
  15 minutes from the scheduler) reminds a card's members once when it's due within a day
  (`cards.due_reminded_at`, cleared when the due date changes).
- **Mentions:** `@Full Name` in a comment mentions that project member (`App\Support\Mentions`,
  whole names, case-insensitive, longest first). Typing `@` in a comment suggests members
  (`components/card/MentionTextarea.vue`). New mentions notify (edits only notify people
  mentioned for the first time); `CommentResource` wraps them in `<span class="mention">`.
- **Templates:** "Make template" in the card dialog sets `cards.is_template`; the card shows a
  "Template" badge and a banner. Lists get a template button next to "Add a card" when the board
  has templates; `cards.from-template` copies one (everything but comments, via `CopyCard`) to the
  bottom of the list and opens it. "Add a checklist" can copy the items (unticked) of any checklist
  on the board (`copy_from`, the lazy `checklists` prop).
- **Calendar** (`components/board/BoardCalendar.vue`): "Board / Calendar" in the ⋯ menu,
  remembered per project in localStorage. A month grid (Monday first, six weeks) shows the cards
  with a due date on their day, honouring the search and filters; a dot shows the due state.
  Dragging a card to another day moves its due date and keeps the time.
- **Shortcuts** (`components/board/useBoardKeys.ts`, `BoardShortcuts.vue`): `/` search, `n` add a
  card to the list holding focus (or the first), arrow keys between visible cards (`data-stack`,
  `data-card-id`), Enter opens, `?` (or "Keyboard shortcuts" in the ⋯ menu) lists them. `busy()` skips them while
  typing or when a dialog, popover or menu is open.
- **Card limits and collapsing:** "Card limit…" in the list menu sets `stacks.wip_limit` (shared,
  1–999); the header then shows "3 / 5", red when over, and nothing is blocked. "Collapse list" is
  personal (localStorage `stackr.collapsed.<project>`): the list shrinks to a narrow column with
  its name on its side and the card count; click it to expand. Collapsed lists can still be dragged.
- **Background photos:** "Background photo" in the project's edit dialog uploads at once
  (`ProjectBackgroundController`, `SetBackground`): JPEG/PNG/WebP up to 20 MB, shrunk to 2400px
  WebP when wider, stored privately under `backgrounds/{project}/` and served to members only
  (`projects.background`, `?v=<file name>` so it caches for good). The board page stands on it
  under a black veil that deepens downwards (45% → 70%); overview tiles show it under their colour.
  Lists and cards keep their opaque wells, so they stay readable; the top bar's search field is
  frosted (`backdrop-blur`) so it holds up over a busy photo.
- **Watching:** `card_watchers` and `project_watchers` (pivots). "Watch" in the card dialog's Actions
  and as "Watch board" in the ⋯ menu (`cards.watch` / `projects.watch`, DELETE to stop); cards you watch show
  an eye. `NotifyWatchers` sends `CardUpdated` (bell only, never email: moves would flood inboxes)
  to the card's watchers, its members (always, so their Watch button shows "Watching", disabled) and
  the board's watchers, minus the actor and anyone who got a more specific notification (the person
  just added, people mentioned). It runs from `RecordActivity` (the line comes from
  `App\Support\ActivityText`) and for new comments. Leaving a board drops your watches there.
- **Projects** have a name, an optional description, an optional link (`url`, http/https), and a colour.
  Description and link show in an "Info" dialog from the board's ⋯ menu (only when one is set). Editing and deleting happens from the ⋮ menu on the overview tiles.
- **Landing page** (`pages/site/Welcome.vue`) for guests at `/`; signed-in users still go straight to
  `/projects`. Built like the kimiyu.ch app sites (wuff, croc): header with anchors and a "Log in" pill,
  hero with the app icon, a static board mock (`components/site/BoardMock.vue`), a dark "How it works"
  band (projects, lists, cards), a 4×2 features grid, "Under the hood" facts, and the footer
  "made with ❤️ by kimiyu.ch". `/legal` (`pages/site/Legal.vue`) is the small print: data, privacy
  (cookies, Hostpoint, mail, 30-day backups), deleting your account, no warranty, contact via GitHub
  issues. Pages under `site/` get no app layout (`app.ts`). Both work in dark mode: the dark band and
  the icon tile use a fixed dark colour, not `--color-ink` (which turns light in dark mode).
- **Favicons** are linked as SVG + PNG (`?v=3`), not `.ico`: Herd answers `/favicon.ico` with a 404
  status, which browsers ignore. `favicon.ico` still exists for direct requests.
- **Auth pages** use `AuthCardLayout.vue`: a centred white card with the logo, title and fields.
  Passkeys can be managed in Security settings but are not offered on the login screen.
  The login has no "Remember me"; instead sessions last 12 hours (`SESSION_LIFETIME=720`, set it in
  production's `.env` too, along with `APP_NAME=Stackr`).
- `/dashboard` redirects to `/projects`, so Fortify's home setting and the old links keep working.

- **Code layout:** controllers stay thin: authorize (`Gate::authorize`), take a form request,
  call one action, toast (`$this->toast()` on the base `Controller`), redirect. Every write is an
  action class in `app/Actions/<Area>/` with a `handle()` method, injected into the controller
  method. Validation lives in `app/Http/Requests`, and props are built by API resources in
  `app/Http/Resources` (`JsonResource::withoutWrapping()`, so there's no `data` key).
- **Tabs, not spaces**, everywhere except JSON, YAML and Markdown (`.editorconfig`). Pint can't
  indent with tabs, so PHP is formatted by PHP-CS-Fixer with Pint's `laravel` rules
  (`.php-cs-fixer.dist.php`, the same as tutor.kimiyu.ch): `composer format` / `composer format:check`.
  The frontend uses `useTabs` in `vite.config.ts`. `resources/js/components/ui/*` (shadcn) is left as it is.
  The switch is listed in `.git-blame-ignore-revs` (`git config blame.ignoreRevsFile .git-blame-ignore-revs`).

## Where things are

- Backend: `app/Http/Controllers/*`, `app/Actions/{Projects,Stacks,Cards,Labels,Checklists,Attachments,Comments,Members}/*`,
  `app/Http/Requests/*`, `app/Http/Resources/*`, `app/Policies/ProjectPolicy.php`,
  `app/Models/{Project,ProjectMembership,Stack,Card,Activity}.php`, `routes/web.php`,
  `routes/console.php` (schedule), `app/Console/Commands/*` (backup, due reminders, thumbnails)
- Notifications: `app/Notifications/*` (`CardNotification` base), `app/Http/Controllers/{Notification,Watch}Controller.php`,
  `app/Support/{Mentions,ActivityText,Thumbnail}.php`
- Card backend: `app/Http/Controllers/{Card,Label,CardLabel,CardMember,Checklist,ChecklistItem,Attachment,Comment}Controller.php`,
  `app/Models/{Label,Checklist,ChecklistItem,Attachment,Comment}.php`, `app/Support/Markdown.php`
- Frontend: `resources/js/pages/projects/{Index,Show}.vue` (Show provides the
  board context and filter and teleports its search and menu into the top bar), `resources/js/components/board/*` (board, calendar, filter bar, archive
  panel, shortcuts), `resources/js/components/card/*` (dialog, pickers, sections, move/copy, mention
  textarea; `board.ts` holds the shared helpers and `describeActivity()`),
  `resources/js/components/projects/*`, `resources/js/components/{AppTopBar,AppMenu,NotificationsDialog}.vue`,
  `resources/js/layouts/{AppLayout,settings/Layout,auth/AuthCardLayout}.vue`
- Landing page and legal notice: `resources/js/pages/site/{Welcome,Legal}.vue`,
  `resources/js/components/site/{SiteHeader,SiteFooter,BoardMock}.vue`
- Tests: `tests/Feature/*Test.php` (one file per area: Project, ProjectMembers, Board, CardDetails, Archive,
  MoveCopyCard, Activity, Notification, Watch, Template, Thumbnail, Background, …), `tests/Unit/FractionalIndexTest.php`
  (162 tests on 2026-10-24)

## Production

Live at https://stackr.kimiyu.ch (Hostpoint, `pumutuxu@pumutuxu.myhostpoint.ch:~/www/stackr.kimiyu.ch`,
the subdomain's document root is `public/`; certificate set up by the user in the Hostpoint panel).
First deployed 2026-10-24; the user registered there and confirmed that mail (verification) arrives.
Same method as the kimiyuch projects (gotaticket/usverchauft):

- **Deploy:** `./deploy.sh` from the Mac (own SSH key). It refuses uncommitted changes and a missing
  `public/build`, runs the tests, exports `HEAD` with `git archive`, runs `composer install --no-dev`
  in a temp folder, rsyncs it up, then runs `migrate --force`, `app:make-thumbnails`, `optimize` and
  `queue:restart`.
  **`public/build` is committed**: run `npm run build` before committing frontend changes.
- **Never touched by deploys:** `.env`, `storage/` (attachments, backups), `database/database.sqlite`.
- **PHP 8.5:** web via `Use php-fpm php85` in the versioned `public/.htaccess` (also HTTPS-only,
  hidden files 403, security headers, caching, compression); CLI and cron always
  `/usr/local/php85/bin/php` (plain `php` on the server is 8.3). `public/.user.ini` raises the upload
  limits (20 MB per file, 420 MB per request; the server default is 64 MB for both).
- **Server `.env`:** production, debug off, own `APP_KEY`, `SESSION_LIFETIME=720`,
  `SESSION_SECURE_COOKIE=true`, mail via Hostpoint SMTP as `bot@kimiyu.ch` (credentials copied from
  wallaby's `.env`).
- **Crontab** (append-only, the account hosts many other sites):
  `* * * * * cd ~/www/stackr.kimiyu.ch && php85 artisan queue:work --stop-when-empty` and
  `0 3 * * * … php85 artisan app:backup-database` and (since 2026-10-24)
  `* * * * * … php85 artisan schedule:run` for the due-date reminders (`routes/console.php`).
  Before editing it, save a copy (`crontab -l > ~/crontab.backup-<date>`).
- **Backups:** `app:backup-database` writes a consistent snapshot (`VACUUM INTO`), gzipped, to
  `storage/app/private/database_*.sqlite.gz` and deletes ones older than 30 days. Attachments live
  next to it in `storage/app/private/attachments` and aren't in the database backup.
  `./scripts/pull-backups.sh` copies the database backups and mirrors the attachments to
  `~/Backups/stackr` on the Mac (by hand). Restore: take a fresh backup, then on the server
  `gunzip -c storage/app/private/<file>.sqlite.gz > database/database.sqlite`.
- **SQLite** in production is fine for a small team: writes are short and serialised, and the
  sessions, cache and queue live in the same file.
- **Server access rules:** only write inside `~/www/stackr.kimiyu.ch`, crontab append-only, never
  print other sites' secrets, ask before anything destructive.

## Running it

- Herd serves it at https://stackr.web.test (the Webroot folder is parked in Herd; secured with
  `herd secure stackr.web`, `APP_URL` is https).
- `npm run dev` while you work. Without it, the site uses the last `npm run build`.
- Checks: `php artisan test`, `vendor/bin/phpstan analyse --memory-limit=1G`, `composer format:check`,
  `npm run types:check`, `npm run check`. Then `npm run build` (the build is committed) and
  `./deploy.sh` when asked to deploy.
- Account: `m@marceli.to`, email verified by hand in the database. For screenshots, throwaway
  users (`ledger-demo@stackr.test` and `amy-demo@stackr.test`, both `password`) are created and
  deleted again each time; `email_verified_at` isn't fillable, so verify them with `forceFill`.
- Headless screenshots: puppeteer-core in `/tmp/pp` against the system Chrome (not part of the repo).
  Dragging needs `card.drag(target)` + `target.drop(card)` (SortableJS uses native HTML5 drag).
  Check new pages with `prefers-color-scheme: dark` too: the app follows the system setting.
- `.hallmark/` (untracked) is the Hallmark design skill's local log.

## Next up

Feature list (2026-10-24). 1–14 are done (one commit each on 2026-10-24); 15–19 are left.

**After the deploy of 2026-10-24** (production is on `19807cb`):

- Check by hand on production: drag & drop with a filter on, the archive panel, move to another
  board, the bell, an email for an assignment (the queue cron sends it), a background photo upload,
  the join link, a due-date reminder (the `schedule:run` cron is in).

**Cards**

1. ~~Archive instead of delete: archive cards and lists, an archive view, restore.~~ Done.
2. ~~Move or copy a card from the dialog: to another list or another project, at a chosen position.~~ Done.
3. ~~Activity log per card, shown together with the comments.~~ Done.
4. ~~@mentions in comments, with a notification to the person mentioned.~~ Done.
5. ~~Cover colours: a plain colour cover as well as image covers.~~ Done.
6. ~~Image thumbnails: covers and previews currently load images at full size.~~ Done.
7. ~~Card templates and duplicating a checklist.~~ Done.

**Board**

8. ~~Search and filters: by text, label, member or due date ("my cards", "overdue").~~ Done.
9. ~~Calendar view of cards with a due date.~~ Done.
10. ~~Keyboard shortcuts: `n` new card, `Esc` close, arrow keys between cards, `/` search.~~ Done.
11. ~~WIP limits or collapsing a list.~~ Done.
12. ~~Board backgrounds: an image as well as the project colour.~~ Done.

**People and notifications**

13. ~~Notifications: in-app bell plus email when you're assigned, mentioned, or a due date is near.~~
    Done; production runs it via the `schedule:run` cron line (see "Production").
14. ~~Watching a card or board, to get notified about changes.~~ Done (bell only).
15. Avatars: upload a profile picture instead of initials.
16. Hand over ownership: the owner can't leave a project, and there's no way to pass it on.

**Overview and account**

17. "My cards" page: everything assigned to me across all projects, sorted by due date.
18. Starring or pinning projects, and archiving projects.
19. Export a project (JSON or CSV) as a backup.

**Agents**

20. MCP server so AI agents (Claude Code etc.) can work a board: read the cards in a list, update
    a card, comment, move it (e.g. To do → Pending → Done). Plan: `laravel/mcp` with an HTTP
    server at `/mcp` (plain requests, so it runs on shared hosting), personal access tokens via
    Sanctum (create/revoke in settings, sent as a Bearer header), tools that call the existing
    actions (so permissions, activity log and notifications behave the same): list projects,
    get board, get card, create card, update card, add comment, move card (list by name),
    tick checklist items. Activity shows the token's user; maybe mark it "via agent".

Not planned: realtime updates (no long-running processes on shared hosting), and browser tests
(Playwright): the user tests the UI by hand. After UI changes, list what to check by hand, especially
drag & drop (cards open on click, drag uses SortableJS's native HTML5 drag).
