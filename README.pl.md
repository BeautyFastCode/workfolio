# Workfolio

[English version](README.md)

Workfolio to demonstracyjny projekt zbudowany w PHP z użyciem Symfony oraz strona portfolio programisty.
Pokazuje, jak połączyć publiczną stronę, zarządzanie treścią, formularz kontaktowy i aktualizacje na żywo w jednej aplikacji.
Korzysta z narzędzi typowych dla projektów Symfony do tworzenia stron, formularzy, obsługi bazy danych i zadań w tle.

## Główne funkcje

- Strona i treści w języku polskim i angielskim.
- Strony składane z gotowych bloków tekstu, obrazów i treści.
- Sekcje portfolio z zadaniami, technologiami i statystykami.
- Panel administracyjny do edycji treści i zarządzania dostępem.
- Formularz kontaktowy zapisujący wiadomości i wysyłający powiadomienia e-mail w tle.
- Zmieniające się zachęty do kontaktu (CTA), aktualizowane bez przeładowania strony.
- Jasny i ciemny motyw.

## Szybki start

Potrzebujesz Dockera z Docker Compose w wersji co najmniej 2.10 oraz GNU Make.
PHP i Composer działają w kontenerach; Node.js nie jest potrzebny.

Domyślna konfiguracja używa następujących portów:

- `80`: dostęp do strony przez HTTP.
- `443/TCP`: dostęp do strony przez HTTPS.
- `443/UDP`: dostęp przez HTTPS z użyciem HTTP/3.
- `8025`: podgląd poczty w Mailpit w środowisku deweloperskim.
- `1025`: odbieranie e-maili deweloperskich przez SMTP w Mailpit.

### Środowisko deweloperskie

Aby oglądać wysyłane e-maile w Mailpit, przed startem dodaj ustawienie do nieśledzonego przez Git pliku `.env.local`:

```dotenv
MAILER_DSN=smtp://mailpit:1025
```

Domyślna konfiguracja poczty pomija wysyłkę e-maili.

Uruchom polecenia z głównego katalogu repozytorium:

```bash
make start
make sf c='doctrine:database:create --if-not-exists'
make sf c='doctrine:migrations:migrate --no-interaction'
```

Pierwszy start automatycznie instaluje zależności.
Jeśli potrzebujesz zainstalować je ponownie, użyj `make composer c='install'`.

Do lokalnego podglądu możesz wczytać przykładowe strony i treści.
**To polecenie zastępuje istniejące dane w bazie, w tym konta administratorów.**
Używaj go tylko na bazie deweloperskiej, której dane możesz usunąć:

```bash
make sf c='doctrine:fixtures:load'
```

Utwórz własne konto administratora i wybierz `ROLE_ADMIN` lub `ROLE_SUPER_ADMIN`, gdy polecenie zapyta o rolę:

```bash
make sf c='app:admin-user:create'
```

Otwórz w przeglądarce:

- Strona: `https://localhost`.
- Panel administracyjny: `https://localhost/admin/pl`.
- Podgląd poczty Mailpit: `http://localhost:8025`.

Przeglądarka może poprosić o zaakceptowanie lokalnego certyfikatu HTTPS.

Jeśli nie wczytujesz przykładowych danych, utwórz i opublikuj treści w panelu administracyjnym.
Strona główna wymaga obecnie opublikowanej strony na pozycji `3`; bez niej `/en` i `/pl` zwracają błąd 404.

Kolejne uruchomienia wykonuj przez `make up`, zatrzymuj środowisko przez `make down`, a logi przeglądaj przez `make logs`.

### Środowisko produkcyjne

Skieruj domenę na serwer i udostępnij ruch HTTP oraz HTTPS.
Ustaw poniższe wartości w środowisku wdrożeniowym lub nieśledzonym przez Git pliku `.env.prod.local`:

```dotenv
SERVER_NAME=portfolio.example.com
APP_SECRET=replace_with_a_random_secret
CADDY_MERCURE_JWT_SECRET=replace_with_another_random_secret
MAILER_DSN=smtp://user:password@smtp.example.com:587
MAILER_FROM_ADDRESS=portfolio@example.com
MAILER_TO_ADRESS=contact@example.com
```

Zastąp przykłady własną domeną, ustawieniami poczty i dwoma różnymi sekretami.
Każdy sekret możesz wygenerować poleceniem `openssl rand -hex 32`.

Zbuduj i uruchom środowisko produkcyjne, a następnie utwórz administratora:

```bash
make start ENV=prod
make sf ENV=prod c='app:admin-user:create'
```

Usługa strony automatycznie wykonuje migracje bazy przy starcie.
Dodaj i opublikuj treści w panelu administracyjnym, w tym stronę główną na pozycji `3`.
Strona będzie dostępna pod Twoją domeną, a panel pod adresem `/admin/pl`.

Produkcja korzysta z osobnego obrazu bez narzędzi deweloperskich.
Strona, proces wysyłający e-maile w tle i harmonogram zadań uruchamiają się razem.
Dane SQLite i przesłane pliki są przechowywane w trwałych wolumenach Dockera.
Do kolejnych uruchomień i zatrzymań używaj `make up ENV=prod` oraz `make down ENV=prod`.

## Stos technologiczny

### Symfony i typowe narzędzia jego ekosystemu

- **PHP 8.5**: Język, w którym napisana jest aplikacja.
- **Symfony 8.1**: Obsługuje żądania i łączy funkcje aplikacji.
- **Doctrine ORM**: Odczytuje i zapisuje dane aplikacji.
- **Doctrine Migrations**: Porządkuje zmiany struktury bazy danych.
- **Twig / Twig Components**: Buduje strony z szablonów i gotowych elementów.
- **Forms / Validator**: Buduje formularze i sprawdza przesłane dane.
- **Security**: Obsługuje logowanie i uprawnienia dostępu.
- **Translation**: Obsługuje teksty po polsku i angielsku.
- **Mailer / Notifier**: Przygotowuje e-maile i inne powiadomienia.
- **Messenger**: Przetwarza zadania z kolejki w tle.
- **Scheduler**: Uruchamia cykliczne zadania.
- **Mercure**: Wysyła aktualizacje na żywo do przeglądarki.
- **UX Turbo / Stimulus**: Odświeża fragmenty strony i dodaje interakcje.
- **AssetMapper / Importmap**: Udostępnia CSS i JavaScript bez budowania przez Node.js.
- **Monolog**: Zapisuje zdarzenia i błędy aplikacji.
- **Composer / Flex**: Instaluje pakiety PHP i konfigurację Symfony.

### Dodatkowe narzędzia

- **EasyAdmin**: Udostępnia panel do zarządzania treścią.
- **SQLite**: Przechowuje bazę danych w jednym pliku.
- **Bootstrap**: Dostarcza style i gotowe elementy interfejsu.
- **Font Awesome**: Dostarcza ikony interfejsu.

### Środowisko, logi i analityka

- **FrankenPHP / Caddy**: Uruchamia PHP i udostępnia stronę przez HTTPS.
- **Docker Compose**: Uruchamia i łączy kontenery aplikacji.
- **Make**: Udostępnia krótkie polecenia do pracy z projektem.
- **Mailpit**: Przechwytuje e-maile do lokalnego podglądu.
- **Umami**: Opcjonalnie zlicza wizyty i zdarzenia na stronie.
- **Grafana Alloy**: Zbiera logi kontenerów i przesyła je do Loki.
- **Loki**: Przechowuje logi i pozwala je przeszukiwać.
- **Grafana**: Udostępnia wspólny widok do przeglądania i analizy logów.

### Testy i jakość kodu

- **PHPUnit**: Sprawdza działanie aplikacji automatycznymi testami.
- **Xdebug**: Ułatwia debugowanie i mierzy pokrycie testami.
- **PHPStan**: Wykrywa błędy PHP bez uruchamiania aplikacji.
- **Deptrac**: Sprawdza granice między modułami i warstwami.
- **PHP CS Fixer / Twig CS Fixer**: Sprawdza i formatuje pliki PHP oraz Twig.
- **Biome**: Sprawdza i formatuje JavaScript oraz CSS.

## Jak działa aplikacja

Symfony przygotowuje strony na serwerze za pomocą Twig, a dane odczytuje z SQLite przez Doctrine.
Turbo i Stimulus odpowiadają za interakcje w przeglądarce.

Za działanie aplikacji odpowiadają trzy kontenery Dockera:

- `php` udostępnia stronę i panel administracyjny przez FrankenPHP i Caddy oraz obsługuje aktualizacje na żywo przez Mercure.
- `php-worker` uruchamia Messengera i pobiera z kolejki powiadomienia e-mail do wysłania przez serwer poczty.
- `php-scheduler` uruchamia cykliczne zadania, które wybierają i publikują zmieniające się komunikaty CTA.

Wysłanie formularza kontaktowego zapisuje wiadomość w bazie i dodaje powiadomienie e-mail do kolejki Messengera.
Osobny proces wysyła e-mail, dzięki czemu odwiedzający nie czeka na odpowiedź serwera poczty.
Kolejka również korzysta z SQLite, więc nie wymaga osobnego kontenera bazy ani kolejki.

Scheduler wywołuje cykliczne zmiany CTA, które Mercure przesyła do przeglądarki.
Turbo podmienia fragment z CTA bez przeładowania strony.

W środowisku deweloperskim działa dodatkowo czwarty kontener, Mailpit, który przechwytuje e-maile do lokalnego podglądu.

Opcjonalna analityka Umami korzysta z zewnętrznej usługi konfigurowanej osobno.

```mermaid
flowchart LR
    Browser[Przeglądarka] --> Web
    subgraph php["php: strona i panel administracyjny"]
        Web[Caddy / FrankenPHP] --> App[Symfony / Twig]
        Mercure[Mercure]
    end
    subgraph worker["php-worker: wysyłka e-maili"]
        Worker[Messenger]
    end
    subgraph scheduler["php-scheduler: cykliczne zadania"]
        Scheduler[Scheduler]
    end
    App -->|Doctrine| Database[(SQLite)]
    App -->|Dodanie e-maila do kolejki| Database
    Database -->|E-mail z kolejki| Worker
    Worker --> Mail[Serwer poczty / Mailpit w dev]
    Scheduler -->|Publikacja CTA| Mercure
    Mercure -->|Aktualizacje na żywo| Browser
```

### Logi

Konfiguracja logowania jest przygotowana do współpracy z zewnętrznym stackiem Alloy, Loki i Grafana.
Monolog zapisuje logi aplikacji w formacie JSON na wyjście kontenerów, skąd trafiają do logów Dockera.
Etykiety kontenerów pomagają rozpoznać, z której usługi i środowiska pochodzą logi.

Po podłączeniu stacku Alloy zbiera logi Dockera i przesyła je do Loki, które je przechowuje i pozwala przeszukiwać.
Grafana udostępnia wspólny widok do przeglądania błędów i zdarzeń z całej aplikacji.
Logi zawierają też czas wykonania i wynik wybranych operacji.
Stack do obsługi logów działa osobno od kontenerów dostarczanych w tym repozytorium.

## Architektura kodu

Workfolio to **modułowy monolit**: jedna aplikacja z modułami odpowiedzialnymi za osobne obszary strony.
Moduły korzystają ze wspólnej bazy i są wdrażane razem, ale każdy ma własny kod i modele danych.

```mermaid
flowchart TB
    Workfolio[Workfolio] --> Content["Content<br/>Strony, bloki, nawigacja"]
    Workfolio --> Portfolio["Portfolio<br/>Zadania, technologie, statystyki"]
    Workfolio --> Contact["Contact<br/>Dane kontaktowe i wiadomości"]
    Workfolio --> CallToAction["CallToAction<br/>Zmieniające się zachęty do kontaktu"]
    Workfolio --> Identity["Identity<br/>Konta administratorów i logowanie"]
    Workfolio --> Shared["Shared<br/>Wspólny kod używany przez moduły"]
```

### Wnętrze modułu

Kod jest pogrupowany według funkcji, np. `SubmitContactMessage` w module `Contact` odpowiada za wysłanie wiadomości.
Każda funkcja ma w jednym miejscu obsługę wejścia, logikę działania i połączenia z narzędziami.
Taki podział według funkcji to **vertical slices**.
Układ warstw jest inspirowany **Clean Architecture** i **Architekturą Heksagonalną**: reguły działania są oddzielone
od dostępu do bazy, wysyłki poczty i Symfony.
Projekt dostosowuje te pomysły do swoich potrzeb, bez ścisłego odwzorowania któregokolwiek wzorca.

```text
src/Contact/
├── SubmitContactMessage/
│   ├── UI/                # Przyjmuje dane z formularza
│   ├── Application/       # Koordynuje zapis i powiadomienie
│   └── Infrastructure/    # Łączy z bazą danych i pocztą
├── ListContactLinks/      # Inna funkcja z takim samym podziałem
├── Shared/                # Kod wspólny tylko dla modułu Contact
└── services.yaml          # Łączy klasy i narzędzia tego modułu
```

- **UI** przyjmuje dane ze strony, panelu administracyjnego lub polecenia w konsoli.
- **Application** wykonuje jedną operację przez jej handler i określa potrzebne narzędzia.
- **Infrastructure** dostarcza te narzędzia, np. dostęp do bazy lub wysyłkę poczty.
- **Domain**, gdy jest potrzebne, zawiera reguły i typy niezależne od Symfony.

Powstają tylko warstwy potrzebne danej funkcji.
Prosta edycja danych w panelu może korzystać bezpośrednio z Doctrine.
Moduły nie sięgają do modeli bazy ani kodu technicznego innych modułów.
`Shared` wewnątrz modułu należy do tego modułu, a `src/Shared` zawiera wspólny kod używany przez różne moduły.

## Struktura katalogów

| Katalog | Zawartość |
| --- | --- |
| `src/` | Moduły i kod aplikacji |
| `bin/` | Polecenia konsoli, testów i kontroli pokrycia |
| `templates/` | Szablony stron, komponentów, e-maili i panelu |
| `assets/` | JavaScript, CSS, fonty i obrazy |
| `translations/` | Teksty polskie i angielskie według modułów |
| `config/` | Konfiguracja aplikacji i pakietów |
| `migrations/` | Zmiany bazy danych według roku i miesiąca |
| `tests/` | Testy podzielone tak jak kod aplikacji |
| `public/` | Punkt wejścia strony i publicznie dostępne pliki |
| `.infrastructure/` | Konfiguracja Dockera, Caddy i środowisk |
| `var/` | Lokalna baza, pamięć podręczna, logi i raporty testów |
| `vendor/` | Pakiety PHP instalowane przez Composer |

## Testy i jakość kodu

Uruchom poniższe polecenia w środowisku deweloperskim:

```bash
make test
make coverage
make composer c='qa'
```

`make test` uruchamia testy PHPUnit, w tym kontrole granic modułów, tłumaczeń i konfiguracji.
Wybraną część testów uruchomisz np. przez `make test c='--filter HomeControllerTest'`.

`make coverage` mierzy, jaką część kodu wykonują testy, i zapisuje raporty w `var/coverage/`.
Sprawdza też, czy pokrycie nie spadło poniżej progu w `coverage-ratchet.json`.
Nowy i zmieniany kod modułów powinien mieć co najmniej 90% pokrycia linii.

`make composer c='qa'` sprawdza formatowanie PHP, Twig, JavaScript i CSS, uruchamia PHPStan na poziomie 10
oraz kontroluje reguły architektury przez Deptrac.
Pozostałe polecenia znajdziesz przez `make help`.

## Licencja

Projekt jest udostępniany na [licencji MIT](LICENSE).
