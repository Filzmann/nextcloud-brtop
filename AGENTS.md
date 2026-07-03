# AGENTS.md - BRTop

## Projekt

Nextcloud-App `brtop` fuer Betriebsrats-Sitzungen, TOP-Listen, Ladungen, Protokolle und Dokumenterzeugung.

Lokale App-URL in der gemeinsamen DDEV-Umgebung:

    https://nextcloud-dev.ddev.site/apps/brtop/

Nextcloud-App-ID:

    brtop

## Git- und Arbeitsregeln

- Dieses Verzeichnis ist ein eigenstaendiges Git-Repository fuer die BR-App `brtop`.
- Andere eigene Nextcloud-Apps, zum Beispiel `adplaner`, leben in eigenen Repositories.
- Keine Commits, kein Push und kein Deployment ohne ausdrueckliche Freigabe durch Simon.
- Vor Commits immer `git status --short`, `git diff --stat` und `git diff --name-only` zeigen.
- Nicht `git add .` verwenden; Dateien gezielt stagen.
- Aenderungen klein, pruefbar und rueckbaubar halten.

## DDEV

Die gemeinsame lokale Nextcloud-DDEV-Umgebung liegt ausserhalb dieses Repos:

    ~/projects/br-nextcloud-apps/nextcloud-dev

Wichtige Pruefungen:

    ddev exec -d /var/www/html/html php occ status
    ddev exec -d /var/www/html/html php occ app:list
    ddev exec -d /var/www/html/html php occ upgrade

In Codex-Sessions koennen DDEV-Befehle im normalen Sandbox-Kontext nicht zuverlaessig auf Docker zugreifen. Wenn `ddev` mit Docker-/Stream-FD-Fehlern scheitert, den gleichen Befehl mit eskaliertem Zugriff erneut ausfuehren.

## Architekturregeln

- Controller bleiben duenn.
- Fachlogik, Datenzugriff, Darstellung, Dokumenterzeugung und Dateiablage werden getrennt.
- Wiederkehrende Logik wird nicht mehrfach in Controllern oder `main.js` dupliziert.
- Persistente Kernobjekte bekommen Modelle/DTOs oder Value Objects.
- Datenzugriffe laufen ueber Repository-, Store- oder Service-Klassen.
- Services arbeiten bevorzugt mit Modellen/DTOs statt rohen Arrays.
- Groessere HTML-Bloecke werden aus `templates/index.php` in Partials ausgelagert.
- Wiederkehrende Frontend-Logik wird in `js/components/`, `js/modules/` oder `js/repositories/` ausgelagert.
- Fehler werden zentral protokolliert; Nutzer*innen erhalten sichere, knappe Meldungen ohne interne Details.
- Keine Architekturabstraktion wird vorsorglich gebaut. Auslagerung erfolgt, wenn sie konkrete Duplizierung, Testbarkeit oder Wartbarkeit verbessert.

Diese Regeln gelten sinngemaess auch fuer andere eigene Nextcloud-Apps; die fachlichen Anwendungsfaelle bleiben aber getrennt.

## Zielstruktur

```text
brtop/
├── appinfo/
├── css/
├── js/
│   ├── components/
│   ├── models/
│   ├── modules/
│   ├── repositories/
│   └── main.js
├── lib/
│   ├── AppInfo/
│   ├── Controller/
│   ├── Exception/
│   ├── Model/
│   ├── Repository/
│   ├── Service/
│   └── Store/
├── templates/
│   ├── partials/
│   └── index.php
└── tests/
```
