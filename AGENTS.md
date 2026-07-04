# AGENTS.md - BRTop

## Projekt

Nextcloud-App `brtop` fuer Betriebsrats-Sitzungen, TOP-Listen, Ladungen, Protokolle und Dokumenterzeugung.

Lokale App-URL in der gemeinsamen DDEV-Umgebung:

    https://nextcloud-dev.ddev.site/apps/brtop/

Nextcloud-App-ID:

    brtop

## Zielsetzung

BRTop soll den wiederkehrenden Sitzungs- und Dokumentprozess des Betriebsrats abbilden, nicht nur einzelne Dateien erzeugen.

Kernprozess:

- Es gibt einen Betriebsrat mit `n` Mitgliedern; die Mitgliederzahl ist eine Setup- bzw. Konfigurationsvariable.
- Ein BR-Mitglied ist ein Nextcloud-User in der Gruppe `Betriebsrat`.
- Listen, Ersatzmitglieder und Nachladungen bleiben zu Beginn bewusst aussen vor, muessen aber spaeter wieder aufgegriffen werden.
- Die regulaere BR-Sitzung findet in einem konfigurierbaren Rhythmus statt, zunaechst typischerweise woechentlich am Dienstag zu einer konfigurierbaren Uhrzeit.
- Die Einladung erfolgt an einem konfigurierbaren Wochentag vor der Sitzung, zunaechst typischerweise am Freitag vorher.
- Sitzungen werden nicht automatisch vorerzeugt, sondern ueber "naechste Sitzung planen" angelegt.
- Beim Erzeugen einer Einladung wird die Ladungsliste als rechtssicherer Snapshot gespeichert; spaetere Gruppenaenderungen duerfen alte Einladungen nicht veraendern.
- Standard-TOPs, Sitzungstypen und Ausschuesse sollen konfigurierbar werden.
- TOPs und Sub-TOPs werden bis Ebene 3 mit Ueberschrift, Reihenfolge, fachlicher TOP-Art und spaeterem Protokollinhalt in der Datenbank gespeichert.
- Aus denselben gespeicherten Sitzungs- und TOP-Daten werden TOP-Liste fuer Einladung, Mailtext, Protokollvorlage und Dokumente erzeugt.
- Alte Einladungen, Protokolle und Beschlussdokumente sollen ueber eine eigene Dokumentuebersicht mit DB-Metadaten auffindbar sein, nicht nur ueber Dateipfade.
- E-Mail-Versand soll mittelfristig direkt aus der App moeglich sein; die Absenderadresse muss konfigurierbar sein.

Start-Sitzungstypen:

- regulaere BR-Sitzung
- Monatsgespraech
- Betriebsausschuss
- Ausschuss / AG
- freie Sitzung

Aktuelle Ausschuss- bzw. AG-Codes:

- ASA
- DPA
- IKT
- BA
- IBF

Architekturfolge: Refactorings sollen zuerst dieses Prozessmodell, Sitzungstypen, Konfiguration, Agenda-Templates, Ladungssnapshots und Dokumentmetadaten beruecksichtigen, bevor Renderer- oder Controller-Details grossflaechig umgebaut werden.

## Fachliche BR-Logik

Standardstruktur einer regulaeren BR-Sitzung:

1. Protokolle
2. Personelle Angelegenheiten
3. Arbeitsorganisatorisches
4. Bericht aus den Sprechstunden seit der letzten Sitzung
5. Weitere Tagesordnungspunkte

Unter TOP 2 derzeit beruecksichtigt:

- Personelle Einzelmassnahmen nach Paragraf 99 BetrVG.
- Vorlaeufige personelle Massnahmen nach Paragraf 100 BetrVG.
- Anhoerungen zu Kuendigungen nach Paragraf 102 BetrVG.

Einladung:

- Paragraf-99-, Paragraf-100- und Paragraf-102-Faelle duerfen kompakt unter "Personelle Angelegenheiten" zusammengefasst werden.

Protokoll:

- Paragraf-99-, Paragraf-100- und Paragraf-102-Faelle muessen getrennt aufgefuehrt werden.
- Nicht jeder TOP ist beschlussrelevant. TOPs koennen fachlich Gliederungspunkte, Berichte, Beratungen oder Beschluesse sein.
- Ein TOP kann mehrere Beschluesse erfordern.

Beschluesse:

- Jeder beschlussrelevante Fall erhaelt ein eigenes Beschlussdokument.
- Das gilt auch dann, wenn mehrere Faelle gemeinsam abgestimmt wurden.

## Git- und Arbeitsregeln

- Dieses Verzeichnis ist ein eigenstaendiges Git-Repository fuer die BR-App `brtop`.
- Andere eigene Nextcloud-Apps, zum Beispiel `adplaner`, leben in eigenen Repositories.
- Keine Commits, kein Push und kein Deployment ohne ausdrueckliche Freigabe durch Simon.
- Vor Commits immer `git status --short`, `git diff --stat` und `git diff --name-only` zeigen.
- Nicht `git add .` verwenden; Dateien gezielt stagen.
- Aenderungen klein, pruefbar und rueckbaubar halten.
- Fuer groessere Refactorings, neue Datenmodelle oder neue Services soll ein eigener Branch vorgeschlagen werden.

## DDEV

Die gemeinsame lokale Nextcloud-DDEV-Umgebung liegt ausserhalb dieses Repos:

    ~/projects/br-nextcloud-apps/nextcloud-dev

BRTop nutzt gemeinsame Basisbausteine aus der Hilfsapp `localbase`. In der lokalen Nextcloud muss `localbase` aktiviert sein, bevor BRTop vollstaendig lauffaehig ist.

Wichtige Pruefungen:

    ddev exec -d /var/www/html/html php occ app:list | grep -i localbase
    ddev exec -d /var/www/html/html php occ status
    ddev exec -d /var/www/html/html php occ app:list
    ddev exec -d /var/www/html/html php occ upgrade

Diese lokale Nextcloud-Version hat keinen `occ migrations:migrate`-Befehl. App-Migrationen laufen beim Aktivieren der App bzw. ueber `occ upgrade`, wenn Nextcloud einen DB-Upgrade-Bedarf meldet.

In Codex-Sessions koennen DDEV-Befehle im normalen Sandbox-Kontext nicht zuverlaessig auf Docker zugreifen. Wenn `ddev` mit Docker-/Stream-FD-Fehlern scheitert, den gleichen Befehl mit eskaliertem Zugriff erneut ausfuehren.

## Architekturregeln

- Controller bleiben duenn.
- Fachlogik, Datenzugriff, Darstellung, Dokumenterzeugung und Dateiablage werden getrennt.
- Wiederkehrende Logik wird nicht mehrfach in Controllern oder `main.js` dupliziert.
- Persistente Kernobjekte bekommen Modelle/DTOs oder Value Objects.
- Modelle/DTOs werden bei Neu- und Weiterentwicklungen in PHP und JavaScript einheitlich angefasst: `get(...)` fuer ein einzelnes Payload/Row/Objekt, `get_all([...])` fuer Listen, `toArray()` fuer Serialisierung und `save()` nur fuer wirklich persistierbare, store-gebundene Modelle. Nicht persistierbare DTOs duerfen `save()` bewusst mit klarer Fehlermeldung blockieren.
- Modell-Hydration wird von aussen ueber `get(...)` und `get_all([...])` aufgerufen. Hilfsmethoden wie `fromArray` oder `fromRow` bleiben, falls noetig, interne/protected Implementierungsdetails und sind keine oeffentliche Modell-API.
- Neue Modellarbeit fuehrt keine neuen `fromApi`-/`toApi`-Kompatibilitaetsaliase ein. Bestehende PHP-`toApiArray()`-Call-sites duerfen schrittweise auf `toArray()` migriert werden, wenn die betroffene Schicht ohnehin angefasst wird.
- Datenzugriffe laufen ueber Repository-, Store- oder Service-Klassen.
- Services arbeiten bevorzugt mit Modellen/DTOs statt rohen Arrays.
- Groessere HTML-Bloecke werden aus `templates/index.php` in Partials ausgelagert.
- Wiederkehrende Frontend-Logik wird in `js/components/`, `js/modules/` oder `js/repositories/` ausgelagert.
- JavaScript wird gut gekapselt, wiederverwendbar und weitgehend objektorientiert strukturiert. API-Zugriffe gehoeren in Repositories/API-Adapter, Daten in Modelle/ViewModels, Workflows in kleine Services/Controller und Rendering/Eventbindung in Komponenten.
- DRY und KISS gelten gemeinsam: echte Duplizierung wird entfernt, aber einfache Lesbarkeit und klare BRTop-Fachgrenzen bleiben wichtiger als fruehe generische Abstraktionen.
- Gemeinsame UI-Helfer oder Komponenten werden erst nach `localbase` verschoben, wenn sie in mindestens zwei Apps dieselbe Semantik, dieselben Zustaende, Events und Accessibility-Regeln haben.
- Fehler werden zentral protokolliert; Nutzer*innen erhalten sichere, knappe Meldungen ohne interne Details.
- Keine Architekturabstraktion wird vorsorglich gebaut. Auslagerung erfolgt, wenn sie konkrete Duplizierung, Testbarkeit oder Wartbarkeit verbessert.

Diese Regeln gelten sinngemaess auch fuer andere eigene Nextcloud-Apps; die fachlichen Anwendungsfaelle bleiben aber getrennt.

## Learnings pflegen

- Wenn bei der Arbeit ein echtes, wiederverwendbares Projekt-Learning entsteht, soll Codex vorschlagen, es in dieser `AGENTS.md` zu ergaenzen.
- Die Ergaenzung erfolgt erst nach ausdruecklicher Freigabe.
- App-spezifische Learnings werden in diesem App-Repo gespeichert.
- App-uebergreifende Learnings werden im Parent-Workspace dokumentiert und bei Bedarf in die App-`AGENTS.md` uebertragen.
- Neue Regeln muessen dort stehen, wo sie gebraucht werden: BRTop-Fachlogik hier, DDEV-/Repo-/Neue-App-Regeln im Parent bzw. in allen betroffenen App-Repos.

## Tests

Wichtige lokale Pruefungen:

    find js -name '*.js' -print0 | xargs -0 -n1 node --check
    node tests/js/model-smoke.js

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
