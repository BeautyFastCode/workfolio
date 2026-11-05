# Workfolio

[Polska wersja](README.pl.md)

Workfolio is a demo project built with PHP and Symfony, and a portfolio website for a developer.
It shows how to combine a public website, content management, a contact form, and live updates in one application.
It uses tools commonly found in Symfony projects for pages, forms, database access, and background tasks.

## Main features

- Website and content in English and Polish.
- Pages built from reusable text, image, and content blocks.
- Portfolio sections for tasks, technologies, and statistics.
- Administration panel for editing content and managing access.
- Contact form that saves messages and sends email notifications in the background.
- Rotating calls to action (CTA), updated without reloading the page.
- Light and dark themes.

## Quick start

You need Docker with Docker Compose v2.10 or newer and GNU Make.
PHP and Composer run inside the containers; Node.js is not required.

The default setup uses these ports:

- `80`: HTTP access to the website.
- `443/TCP`: HTTPS access to the website.
- `443/UDP`: HTTPS access using HTTP/3.
- `8025`: Mailpit's email viewer in development.
- `1025`: Mailpit's SMTP service for receiving development email.

### Development

To view outgoing email in Mailpit, add this setting to an untracked `.env.local` file before starting:

```dotenv
MAILER_DSN=smtp://mailpit:1025
```

The default mail configuration discards outgoing email.

Run these commands from the repository root:

```bash
make start
make sf c='doctrine:database:create --if-not-exists'
make sf c='doctrine:migrations:migrate --no-interaction'
```

The first start installs dependencies automatically.
If you need to install them again, run `make composer c='install'`.

For a local demo, you can load sample pages and content.
**This command replaces existing database data, including administrator accounts.**
Use it only on a disposable development database:

```bash
make sf c='doctrine:fixtures:load'
```

Create your own administrator account and choose `ROLE_ADMIN` or `ROLE_SUPER_ADMIN` when prompted:

```bash
make sf c='app:admin-user:create'
```

Open these addresses in your browser:

- Website: `https://localhost`.
- Administration panel: `https://localhost/admin/en`.
- Mailpit email viewer: `http://localhost:8025`.

The browser may ask you to accept the local HTTPS certificate.

Without sample data, create and publish content in the administration panel.
The home page currently requires a published page at position `3`; otherwise `/en` and `/pl` return 404.

Use `make up` for subsequent starts, `make down` to stop the environment, and `make logs` to view logs.

### Production

Point your domain at the server and allow HTTP and HTTPS traffic.
Set the following values in the deployment environment or an untracked `.env.prod.local` file:

```dotenv
SERVER_NAME=portfolio.example.com
APP_SECRET=replace_with_a_random_secret
CADDY_MERCURE_JWT_SECRET=replace_with_another_random_secret
MAILER_DSN=smtp://user:password@smtp.example.com:587
MAILER_FROM_ADDRESS=portfolio@example.com
MAILER_TO_ADRESS=contact@example.com
```

Replace the example values with your domain, mail settings, and two different secrets.
You can generate each secret with `openssl rand -hex 32`.

Build and start the production environment, then create an administrator:

```bash
make start ENV=prod
make sf ENV=prod c='app:admin-user:create'
```

The web service applies database migrations on startup.
Add and publish content in the administration panel, including the home page at position `3`.
The website is then available at your domain, with the panel at `/admin/en`.

Production uses a separate image without development tools.
The website, background email worker, and task scheduler start together.
SQLite data and uploaded files are kept in persistent Docker volumes.
For subsequent starts and stops, use `make up ENV=prod` and `make down ENV=prod`.

## Technology stack

### Symfony and its usual tools

- **PHP 8.5**: Application programming language.
- **Symfony 8.1**: Handles requests and connects application features.
- **Doctrine ORM**: Reads and saves application data.
- **Doctrine Migrations**: Tracks changes to the database structure.
- **Twig / Twig Components**: Builds pages from templates and reusable elements.
- **Forms / Validator**: Builds forms and checks submitted data.
- **Security**: Handles login and access permissions.
- **Translation**: Supports English and Polish text.
- **Mailer / Notifier**: Prepares email and other notifications.
- **Messenger**: Processes queued work in the background.
- **Scheduler**: Runs recurring tasks.
- **Mercure**: Sends live updates to the browser.
- **UX Turbo / Stimulus**: Updates page sections and adds interactions.
- **AssetMapper / Importmap**: Serves CSS and JavaScript without a Node.js build.
- **Monolog**: Records application events and errors.
- **Composer / Flex**: Installs PHP packages and Symfony configuration.

### Additional tools

- **EasyAdmin**: Provides the content administration panel.
- **SQLite**: Stores the database in a single file.
- **Bootstrap**: Provides page styles and interface components.
- **Font Awesome**: Supplies interface icons.

### Environment, logs, and analytics

- **FrankenPHP / Caddy**: Runs PHP and serves the website over HTTPS.
- **Docker Compose**: Starts and connects the application's containers.
- **Make**: Provides short commands for common project tasks.
- **Mailpit**: Captures development email for local viewing.
- **Umami**: Optional website visit and event analytics.
- **Grafana Alloy**: Collects container logs and sends them to Loki.
- **Loki**: Stores logs and makes them searchable.
- **Grafana**: Provides a shared view for browsing and analysing logs.

### Tests and code quality

- **PHPUnit**: Checks application behaviour with automated tests.
- **Xdebug**: Supports debugging and measures test coverage.
- **PHPStan**: Finds PHP errors without running the application.
- **Deptrac**: Checks boundaries between modules and layers.
- **PHP CS Fixer / Twig CS Fixer**: Checks and formats PHP and Twig files.
- **Biome**: Checks and formats JavaScript and CSS.

## How the application works

Symfony prepares pages on the server using Twig and reads data from SQLite through Doctrine.
Turbo and Stimulus handle interactions in the browser.

Three Docker containers run the application:

- `php` serves the website and administration panel using FrankenPHP and Caddy, and hosts Mercure for live updates.
- `php-worker` runs Messenger and picks up queued email notifications for delivery to the mail server.
- `php-scheduler` runs scheduled tasks that choose and publish rotating CTA messages.

Submitting the contact form saves the message in the database and queues an email notification through Messenger.
The worker sends it separately, so the visitor does not wait for the mail server to respond.
The queue also uses SQLite, so no separate database or queue container is needed.

Scheduler triggers recurring CTA changes, which Mercure sends to the browser.
Turbo replaces the CTA section without reloading the page.

Development adds a fourth container, Mailpit, to capture email for local viewing.

Optional Umami analytics uses an external service configured separately.

```mermaid
flowchart LR
    Browser[Browser] --> Web
    subgraph php["php: website and administration panel"]
        Web[Caddy / FrankenPHP] --> App[Symfony / Twig]
        Mercure[Mercure]
    end
    subgraph worker["php-worker: email delivery"]
        Worker[Messenger]
    end
    subgraph scheduler["php-scheduler: recurring tasks"]
        Scheduler[Scheduler]
    end
    App -->|Doctrine| Database[(SQLite)]
    App -->|Queue email| Database
    Database -->|Queued email| Worker
    Worker --> Mail[Mail server / Mailpit in dev]
    Scheduler -->|Publish CTA| Mercure
    Mercure -->|Live updates| Browser
```

### Logs

Logging is configured to work with an external Alloy, Loki, and Grafana stack.
Monolog writes application logs as JSON to the container output, where Docker can collect them.
Container labels help identify which service and environment the logs come from.

Once connected, Alloy collects the Docker logs and sends them to Loki for storage and searching.
Grafana provides a shared view for reviewing errors and activity across the application.
Logs also record the duration and outcome of selected operations.
The logging stack runs separately from the containers included in this repository.

## Code architecture

Workfolio is a **modular monolith**: one application with modules responsible for separate parts of the website.
They share a database and deployment, while each module owns its code and data models.

```mermaid
flowchart TB
    Workfolio[Workfolio] --> Content["Content<br/>Pages, blocks, navigation"]
    Workfolio --> Portfolio["Portfolio<br/>Tasks, technologies, statistics"]
    Workfolio --> Contact["Contact<br/>Contact details and messages"]
    Workfolio --> CallToAction["CallToAction<br/>Rotating calls to action"]
    Workfolio --> Identity["Identity<br/>Admin accounts and login"]
    Workfolio --> Shared["Shared<br/>Common code used across modules"]
```

### Inside a module

Code is grouped by feature, such as `SubmitContactMessage` in `Contact`.
Each feature keeps its entry points, application logic, and external tools together.
This grouping is called **vertical slices**.
The layer structure is inspired by **Clean Architecture** and **Hexagonal Architecture**: application rules are kept separate
from database access, email delivery, and Symfony.
The project adapts these ideas to its needs rather than following either model strictly.

```text
src/Contact/
├── SubmitContactMessage/
│   ├── UI/                # Receives form input
│   ├── Application/       # Coordinates saving and notification
│   └── Infrastructure/    # Connects to the database and mailer
├── ListContactLinks/      # Another feature with the same structure
├── Shared/                # Code shared within Contact only
└── services.yaml          # Connects this module's classes and tools
```

- **UI** receives input from a page, the admin panel, or a console command.
- **Application** carries out one operation through its handler and defines what tools it needs.
- **Infrastructure** provides those tools, such as database access or email delivery.
- **Domain**, where needed, holds rules and types independent of Symfony.

Only the layers a feature needs are created.
Simple admin editing can use Doctrine directly.
Modules do not reach into each other's database models or technical code.
`Shared` inside a module belongs to that module; `src/Shared` contains common code used across modules.

## Directory structure

| Directory | Contents |
| --- | --- |
| `src/` | Modules and application code |
| `bin/` | Console, test, and coverage commands |
| `templates/` | Page, component, email, and admin templates |
| `assets/` | JavaScript, CSS, fonts, and images |
| `translations/` | English and Polish text grouped by module |
| `config/` | Application and package configuration |
| `migrations/` | Database changes grouped by year and month |
| `tests/` | Tests grouped like the application code |
| `public/` | Web entry point and publicly served files |
| `.infrastructure/` | Docker, Caddy, and environment configuration |
| `var/` | Local database, cache, logs, and test reports |
| `vendor/` | PHP packages installed by Composer |

## Tests and code quality

Run these commands in the development environment:

```bash
make test
make coverage
make composer c='qa'
```

`make test` runs PHPUnit tests, including checks for module boundaries, translations, and configuration.
To run a smaller selection, use `make test c='--filter HomeControllerTest'`.

`make coverage` measures how much code the tests execute and saves reports in `var/coverage/`.
It also checks that coverage stays above the baseline in `coverage-ratchet.json`.
New and changed module code should have at least 90% line coverage.

`make composer c='qa'` checks PHP, Twig, JavaScript, and CSS formatting, runs PHPStan at level 10,
and checks architecture rules with Deptrac.
See `make help` for the remaining commands.

## License

This project is licensed under the [MIT License](LICENSE).
