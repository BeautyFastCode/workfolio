# Repository Guidelines

Workfolio is a modular Symfony monolith with Twig, Doctrine/SQLite, and AssetMapper/Importmap.
Use Docker and Make for development; there is no Node.js build step.
See `README.md` or `README.pl.md` for setup and deployment, and `make help` for all commands.

## Architecture

- Business modules are `CallToAction`, `Contact`, `Content`, `Identity`, and `Portfolio`.
  Put use cases in `src/<Module>/<VerbObject>/{Application,Infrastructure,UI}` and mirror ownership under `tests/`.
  Create only needed layers.
- Each non-empty business Application slice has exactly one `...Handler` and owns its ports.
  Keep Domain and Application independent of frameworks; Infrastructure implements ports and UI adapts input.
  Use `...Command`, `...Query`, `...Repository`, and `...Finder` for their respective roles; follow nearby `View`/`Result` conventions.
- Do not access another module's entities or infrastructure.
  `<Module>/Shared` is private code shared by that module's slices; `src/Shared` is for stable cross-module behavior.
- Simple EasyAdmin CRUD may use Doctrine entities directly; business rules and side effects go through Application handlers.
- Register services in `src/<Module>/services.yaml`, with explicit handlers and port aliases.
  Limit discovery to Infrastructure and UI; keep `config/services.yaml` as the composition root without broad `App\` discovery.
- Doctrine entities belong in `<Module>/Shared/Infrastructure/Persistence/Doctrine/Entity`, with explicit mappings in
  `config/packages/doctrine.yaml`; migrations go in `migrations/<year>/<month>/`.
- Twig templates follow `templates/<module>/<slice>/`; shared layouts and components go in `templates/shared/`.
  Translations go in `translations/<Owner>/`, using `messages`, `admin`, and `validators` domains with English/Polish key parity.
  Browser assets live in `assets/`; Docker, Caddy, and deployment files live in `.infrastructure/`.

## Commands and Verification

- `make start` builds and starts the development stack; `make up` starts existing images; `make down` stops it.
- `make composer c='install'` installs development dependencies; `make vendor` installs without dev dependencies or scripts.
- `make sf c='about'` runs Symfony commands; `make sh` opens the PHP container.
- `make test` runs PHPUnit; narrow it with `make test c='--filter HomeControllerTest'`.
- `make composer c='qa'` runs PHP/Twig/Biome formatting checks, PHPStan, and Deptrac.
  Run individual Composer scripts from `composer.json` when only part of the checks applies.
- `make coverage` generates reports in `var/coverage/` and checks `coverage-ratchet.json`.

For code changes, cover changed behavior and run focused tests plus relevant formatting/static checks.
For broad or architectural changes, run `make test`, `make composer c='qa'`, and `make coverage`.
Architecture rules are enforced by `tests/Architecture/` and `deptrac.php`.
Keep new and changed module code at least 90% covered and do not lower the coverage ratchet.

## Conventions

Follow nearby code, `.editorconfig`, `.php-cs-fixer.dist.php`, and `biome.json`.
PHP uses strict types and namespaces matching paths; PHPUnit methods use `test_expected_behavior`.
Name classes by business intent, Twig component templates in PascalCase, and Stimulus files `snake_case_controller.js`.

In Markdown, start each sentence on a new source line and keep prose lines around 150 characters or less.
Preserve commands and links; keep `README.md` and `README.pl.md` structure and examples aligned.
Use short, focused, imperative, lowercase commit summaries.
PRs should explain behavior changes, verification, and any migrations, environment, or deployment changes.

## Configuration Safety

Do not commit secrets or local `.env` overrides; document new environment variables.
Check the target SQLite database before loading fixtures, which replace existing data.
For production-sensitive changes, check `.infrastructure/compose.yaml` and `.infrastructure/compose.prod.yaml`,
including persistent database/uploads and the worker/scheduler services.
