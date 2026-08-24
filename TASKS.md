# TASKS

Prioritized findings and recommendations from a code analysis of the app
(Symfony 7.4, CQRS/DDD over Messenger). Ordered by impact.

## 🔴 Critical — security

> ✅ Batch done (commit pending). Added `GameVoter` (ownership), `#[IsGranted]`
> on all mutating actions, POST-only + `#[IsCsrfTokenValid]` on score/delete,
> DOM-based (escaped) rendering in the ticker, and `login_throttling`.

- [x] **Add access control / authorization.** `config/packages/security.yaml`
  has an empty `access_control`, and no controller action checks anything.
  - Anonymous users can reach `/new`; `getUser()` is then `null` → `CreateGame`
    gets a null owner → `Game.owner` is `nullable: false` → 500 instead of a
    login redirect.
  - Any logged-in user can edit/delete/score any game (IDOR). The `owner`
    field exists but is never enforced.
  - Fix: add `access_control` rules (`/new`, `/{id}/edit`, `/delete`, score
    routes → `ROLE_USER`) **and** an ownership check (a `GameVoter` comparing
    `game.owner` to the current user, used via `#[IsGranted]`).

- [x] **Make score changes POST-only + CSRF.** `increasehome`, `decreasehome`,
  `increaseaway`, `decreaseaway` allow `GET` (`src/Controller/GameCommandController.php`).
  Scores can be changed by a link, browser prefetch, crawler, or `<img src>`,
  with no CSRF protection. Restrict to `POST` and add a CSRF token (as `delete`
  already does).

- [x] **Fix stored XSS in the ticker.** `templates/game/index.html.twig`
  `renderGames()` injects `game.home` / `game.away` / `game.location` straight
  into `innerHTML`. These are user-entered and served from `/nextgames.json`.
  Escape them (text nodes / escaping helper) or render server-side with Twig
  auto-escaping.

- [x] **Add login throttling.** The `main` firewall has no `login_throttling`;
  add it to slow credential-stuffing.

## 🟠 Correctness bugs

- [x] **`User::addGame` / `removeGame` call nonexistent methods.**
  `src/Entity/User.php` calls `$game->setUser()` / `$game->getUser()`, but
  `Game` only has `setOwner`/`getOwner`. Any call fatals. Rename to match.

- [x] **`GameEvent` references a missing repository.**
  `src/Entity/GameEvent.php` declares `repositoryClass: GameEventRepository::class`
  (imported from `App\Repository`), but that class doesn't exist. Fails as soon
  as anything does `getRepository(GameEvent::class)`. Create the repo or drop
  the `repositoryClass`.

- [x] **`gameEventNew` renders a nonexistent template on validation failure.**
  `GameCommandController::gameEventNew` renders `game/gameevent_form_error.html.twig`,
  which isn't in `templates/`. Invalid submissions → "template not found" 500.
  Create the template or reuse the existing event form partial.

- [x] **"Last games" ordering is wrong.** `GameRepository::findLastGames()`
  orders `datetime ASC` with `setMaxResults(10)`, returning the *oldest* 10 past
  games instead of the most recent. Change to `DESC`. (`findNextGames` orders by
  `id ASC` rather than `datetime` — make consistent.)

- [x] **Deleting a game with ticker events failed.** `Game::$gameEvents` had
  neither `cascade` nor `orphanRemoval`, so `DeleteGame` on a game that already
  had events died with a 1451 integrity constraint violation (500). The events
  belong to their game and have no life of their own, so the association now
  cascades `persist`/`remove` with `orphanRemoval: true` — no schema change
  needed, Doctrine deletes the children before the parent.

## 🟡 Architecture / dead code

> ✅ Batch done (commit pending). Removed the unused query side; routed all
> writes through a single `GameStateChanged` domain event on the event bus;
> made the projector write JSON atomically (temp + rename). Decisions: query
> side removed (not wired), read models hardened (not migrated off files).

- [x] **Remove or use the query side.** Removed `QueryBus`, `GetLastGames`,
  `GetLastGamesHandler`, the Shared `QueryBus`/`Query` interfaces, the
  `query.bus` in messenger.yaml, and the unused `GameRepository` injection in
  `GameQueryController::index`.

- [x] **Make projection triggering consistent.** All mutating handlers now
  dispatch a single `GameStateChanged` event on the event bus; one
  `GameStateChangedHandler` rebuilds the read models. Replaced the old
  `GameCreated`/`GameCreatedHandler`.

- [x] **Reconsider file-based read models.** Kept the public-JSON ticker but
  made writes atomic (temp file + `rename`), removing the torn-read race.
  Multi-instance / read-only-fs deploys remain a known limitation (documented).

- [x] **`CreateGameHandler` sets the owner twice** — removed the duplicate.

## 🟢 Project hygiene

- [x] **Add tests.** Installed `symfony/test-pack` (PHPUnit 13). Added:
  `GameVoterTest` (ownership authorization), `IncreaseHomePointsHandlerTest`
  (scoring + `GameStateChanged` dispatch), and `SecuritySmokeTest` (public
  landing page, `/new` redirects to login, score routes reject GET). 9 tests,
  16 assertions, green. Next: DB-backed functional tests for the non-owner
  403 path (needs test fixtures — pairs with the migration work).
- [x] **Add migrations.** Added a baseline migration (`Version20260706192221`)
  with the full schema, generated against MariaDB. Aligned dev/test with prod's
  engine: `compose.yaml` now runs **MariaDB 10.11** (self-provisioning `app`
  user + `tickerly` / `tickerly_test` DBs via `docker/db/init`), replacing the
  stale Postgres flex compose files. `.env` default is now MariaDB. Dev + test
  DBs migrate cleanly and validate in sync; the suite is green against MariaDB.
  Note: prod `serverVersion` in `.env` may need adjusting to the exact 10.11.x.
- [x] ~~**Switch `User::$roles` from Doctrine `array` to `json`.**~~ Non-issue:
  an untyped `#[ORM\Column]` on an `array` property already maps to `json`
  (baseline shows `roles JSON NOT NULL`). No change needed.
- [ ] Minor: `private ?int $homepoints =0;` spacing; consider `NOT NULL
  default 0` for score columns instead of nullable.

## 🔵 Feature ideas

- [x] **Admin area.** `/admin` (ROLE_ADMIN, guarded by `access_control`) with an
  overview plus user and game lists that activate/deactivate entries. Both
  entities carry an `active` flag: deactivated users are refused at login by
  `App\Security\UserChecker`, deactivated games drop out of the read models and
  the public show page (owner and admins still see them). Game activation goes
  through the command bus (`SetGameActive` → `GameStateChanged`) so the JSON
  read models are rebuilt. `app:user:promote` grants/revokes `ROLE_ADMIN` and
  bootstraps the first admin.

- [x] **Better URL scheme for games.** All three entities (`User`, `Game`,
  `GameEvent`) moved from auto-increment ints to UUIDv7, assigned in the
  constructor rather than generated by the DB. Games carry a unique `slug`
  (`falcons-vs-sharks-2026-08-25`: teams + kickoff date, counter only when the
  same fixture repeats on a day) and every route is now `/games/...`:
  `/games`, `/games/new`, `/games/{slug}`, `/games/{slug}/edit`,
  `/games/{slug}/increasehome`, `/games/{slug}/delete`, `/games/{slug}/events`.
  Decisions: the slug is assigned once at creation and never regenerated, so a
  ticker link survives a later correction of the team names; `/show/{id}` was
  dropped without a redirect (deliberate — see the migration note below). The
  admin toggles keep the UUID in their POST-only URLs. Read models now carry
  `slug`. Sessions written before the switch were turned into a clean logout by
  a temporary `User::__unserialize()`; it was removed on 2026-08-24, once no
  such session could still be alive after the 2026-08-23 release.

- [x] **Social sharing for a game.** `base.html.twig` gained a `head_meta`
  block; the game page fills it with a canonical link, Open Graph and Twitter
  Card tags (title = `Home 0 : 0 Away`, description = location + kickoff) and
  `noindex` while a game is deactivated. Next to "Aktualisieren" sits a "Teilen"
  button: the Web Share API where it exists (which is what surfaces WhatsApp and
  friends on phones), otherwise the link goes to the clipboard, and if that is
  refused a readonly input with the link is revealed. All four paths were
  exercised headlessly against the rendered script.

  - [ ] Still open (stretch): a dynamic OG preview image per game, so unfurls
    show the scoreboard instead of a bare card. Fits the projector pattern —
    render on `GameStateChanged`, cache as a static asset.

## Suggested order

1. Authorization (#1, #2)
2. XSS (#3)
3. Correctness bugs (#5–#8)
4. CQRS direction (query side / projection), then tests + migrations
