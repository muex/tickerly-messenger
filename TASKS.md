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

## 🟡 Architecture / dead code

- [ ] **Remove or use the query side.** `QueryBus`, `GetLastGames`, and
  `GetLastGamesHandler` (empty `__invoke`) are wired but unused; `GameQueryController::index`
  injects `GameRepository` and ignores it, reading static JSON instead. Either
  delete the query-bus scaffolding or route reads through it.

- [ ] **Make projection triggering consistent.** `CreateGameHandler` dispatches
  a `GameCreated` event → projector (clean). But `IncreaseHomePointsHandler`,
  `UpdateGameHandler`, `DeleteGameHandler`, etc. call `projectReadModels()`
  directly. Pick one pattern — domain events (`PointsChanged`, `GameUpdated`,
  `GameDeleted`) match the design you started.

- [ ] **Reconsider file-based read models.** `GameProjector` does non-atomic
  `file_put_contents` into `public/` — breaks on read-only/containerized or
  multi-instance deploys, with a torn-read race. Confirm publishing all games'
  data publicly is intended (public ticker).

- [ ] **`CreateGameHandler` sets the owner twice** — harmless duplicate, tidy up.

## 🟢 Project hygiene

- [ ] **Add tests.** `tests/` is empty. Cover the command handlers and the
  authorization/ownership logic once added.
- [ ] **Add migrations.** `migrations/` is empty. Generate a baseline migration
  rather than relying on `schema:update`.
- [ ] **Switch `User::$roles` from Doctrine `array` to `json`** (portable,
  modern).
- [ ] Minor: `private ?int $homepoints =0;` spacing; consider `NOT NULL
  default 0` for score columns instead of nullable.

## Suggested order

1. Authorization (#1, #2)
2. XSS (#3)
3. Correctness bugs (#5–#8)
4. CQRS direction (query side / projection), then tests + migrations
