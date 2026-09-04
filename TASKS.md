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

- [x] **The edit command was decorative.** `edit()` bound the form to the
  *managed* `Game`, so `handleRequest()` applied the change and the command that
  followed only described what had already happened — unusable asynchronously,
  and writable by any later flush. The form now binds a plain `GameData` object,
  which leaves the handler as the only writer. `new` uses the same object.

- [x] **Commands carried entities.** `CreateGame` took a `User` and
  `CreateGameEvent` a `Game`, which cannot survive a queue. Both now carry a
  `Uuid`; the handlers load what they write to and fail loudly if it is gone.
  `UpdateGameHandler` checks for null too.

- [x] **The CommandBus port was never injected.** Controllers took the concrete
  `App\Game\Infrastructure\CommandBus` in some actions and Messenger's own
  `MessageBusInterface` in others, so `App\Shared\Domain\CommandBus` appeared
  only in the adapter's `implements`. Everything injects the interface now, as
  the event side already did.

### Open from the same review

- [ ] **Side effects run inside the DB transaction.** `doctrine_transaction`
  wraps the handler, the handler dispatches synchronously, and the projector
  writes its JSON before the commit. A rollback afterwards leaves a file with a
  state the database never had. Events belong after the commit.

- [ ] **The `validation` middleware runs empty.** It hangs on both buses and no
  command carries a constraint — nor does the form, now that `GameData` is the
  obvious place for them.

- [ ] **`GameStateChanged` is a cache signal, not a domain event.** It says that
  something happened, not what. Right for projections, but nothing else can hook
  onto it ("push a notification on a goal"). Split, or rename to what it is.

- [ ] **There is no query side, and `GameQueryController` promises one.** The
  read path goes straight to the repository and renders the aggregate. That is
  the right call at this size; the name is what misleads.

## 🟢 Project hygiene

- [x] **Add tests.** Installed `symfony/test-pack` (PHPUnit 13). Added:
  `GameVoterTest` (ownership authorization), `IncreaseHomePointsHandlerTest`
  (scoring + `GameStateChanged` dispatch), and `SecuritySmokeTest` (public
  landing page, `/new` redirects to login, score routes reject GET). Since
  extended with the slug builder, the decrement clamp, the preview card and the
  session round trip. The DB-backed part is done too: `FunctionalTestCase`
  builds rows in a transaction that is rolled back afterwards and skips itself
  when no database is reachable, so `php bin/phpunit` stays green without a
  container. On it hang `GameAccessTest` (non-owner 403 on edit and events,
  missing CSRF token, admin area closed to ordinary users), `GameSharingTest`
  (OG/canonical tags, the card and its cache-busting URL, deactivated games
  gone for the public but reachable and `noindex` for the owner) and
  `ScoreControlsTest` (minus buttons disabled at nil, none at all for
  visitors). 39 tests, 93 assertions.
- [x] **Add migrations.** Added a baseline migration (`Version20260706192221`)
  with the full schema, generated against MariaDB. Aligned dev/test with prod's
  engine: `compose.yaml` now runs **MariaDB 10.11** (self-provisioning `app`
  user + `tickerly` / `tickerly_test` DBs via `docker/db/init`), replacing the
  stale Postgres flex compose files. `.env` default is now MariaDB. Dev + test
  DBs migrate cleanly and validate in sync; the suite is green against MariaDB.
  Note resolved: the exact patch level in `serverVersion` does not matter.
  DBAL picks the platform in bands (`AbstractMySQLDriver`: 10.4.3, 10.5.2,
  10.6.0, 10.10.0, 11.7, 12.3), so every 10.11.x lands on `MariaDb1010Platform`.
  Only a prod server outside that band would need the value changed — worth one
  look with `SELECT VERSION()` if the host is ever upgraded.
- [x] ~~**Switch `User::$roles` from Doctrine `array` to `json`.**~~ Non-issue:
  an untyped `#[ORM\Column]` on an `array` property already maps to `json`
  (baseline shows `roles JSON NOT NULL`). No change needed.
- [x] Minor: `private ?int $homepoints =0;` spacing; `NOT NULL default 0` for
  the score columns instead of nullable. A game always has a score — 0 : 0
  before anyone taps anything — so "no score yet" and "zero" were never two
  different things. Both columns are now `int` on the entity and
  `INT DEFAULT 0 NOT NULL` in the schema; existing NULLs were filled by
  `Version20260826090000`.

- [x] **Scores could go negative.** `DecreaseHomePoints`/`DecreaseAwayPoints`
  decremented without a floor, so H− on a fresh 0 : 0 game wrote -1 and the
  ticker showed it. Both handlers now clamp at 0, and the − buttons render
  `disabled` while that side is at nil — the guard sits in the handler, so a
  replayed or hand-made POST cannot get around it either.

## 🔵 Feature ideas

- [x] **SEO for the landing page.** Audit found: no meta description anywhere,
  no canonical, no Open Graph on `/`, `robots.txt` and `sitemap.xml` both 404,
  `www.tickerly.de` answering 200 next to the apex (every URL under two names),
  and — the structural one — no crawlable path into any game, because `/games`
  builds its list in JavaScript from the JSON read models. Fixed: canonical in
  the layout for every route, a default description, full OG/Twitter set plus
  `WebSite` JSON-LD on `/`, a preview card for the site itself (`/card.png`),
  `robots.txt`, a generated `/sitemap.xml` (landing pages plus every public
  game), a 301 from www to the apex in `.htaccess`, and a server-rendered
  "Aktuelle Ticker" section that links into the game pages. The h1 now names
  the subject instead of only setting a mood.

  - [ ] Still open: `/games` itself is JavaScript-only. Rendering both lists in
    Twig and keeping the JSON for live updates would make the index crawlable
    and work without JS.


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

  - [x] Dynamic OG preview image: `/games/{slug}/card.png` draws a 1200×630
    scoreboard with GD (`GameCardRenderer`). Rendered on demand rather than on
    `GameStateChanged` — an unfurler asks far less often than the ticker is
    tapped, so nothing is drawn that nobody reads. Cached under a key built from
    the score, which doubles as the `?v=` cache buster and the ETag, so a stale
    card can never be served. Long club names stack onto two lines instead of
    shrinking below the meta line. Where the server has no GD, no FreeType or no
    font, `isAvailable()` is false and the page omits the image tags rather than
    pointing a crawler at a broken URL.

## Suggested order

1. Authorization (#1, #2)
2. XSS (#3)
3. Correctness bugs (#5–#8)
4. CQRS direction (query side / projection), then tests + migrations
