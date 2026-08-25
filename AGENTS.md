# AGENTS.md - BRTop

## Projekt

Nextcloud-App `brtop` fuer Betriebsrats-Sitzungen, TOP-Listen, Ladungen, Protokolle und Dokumenterzeugung.

Lokale App-URL in der gemeinsamen DDEV-Umgebung:

    https://nextcloud-dev.ddev.site/apps/brtop/

Nextcloud-App-ID:

    brtop

Zukünftige Ziele und noch ungeklärte Produktentscheidungen stehen
ausschließlich in `ROADMAP.md`.

## Zielsetzung

BRTop soll den wiederkehrenden Sitzungs- und Dokumentprozess des Betriebsrats abbilden, nicht nur einzelne Dateien erzeugen.

Kernprozess:

- Es gibt einen Betriebsrat mit `n` Mitgliedern; die Mitgliederzahl ist eine Setup- bzw. Konfigurationsvariable.
- Ein BR-Mitglied ist ein Nextcloud-User in der Gruppe `Betriebsrat`.
- Listen, Ersatzmitglieder und Nachladungen sind nicht Bestandteil des
  geltenden Fachvertrags; ihre spätere Ausgestaltung steht in `ROADMAP.md`.
- Die regulaere BR-Sitzung findet in einem konfigurierbaren Rhythmus statt, zunaechst typischerweise woechentlich am Dienstag zu einer konfigurierbaren Uhrzeit.
- Die Einladung erfolgt an einem konfigurierbaren Wochentag vor der Sitzung, zunaechst typischerweise am Freitag vorher.
- Sitzungen werden nicht automatisch vorerzeugt, sondern ueber "naechste Sitzung planen" angelegt.
- Beim Erzeugen einer Einladung wird die Ladungsliste als rechtssicherer Snapshot gespeichert; spaetere Gruppenaenderungen duerfen alte Einladungen nicht veraendern.
- TOPs und Sub-TOPs werden bis Ebene 3 mit Ueberschrift, Reihenfolge, fachlicher TOP-Art und spaeterem Protokollinhalt in der Datenbank gespeichert.
- Aus denselben gespeicherten Sitzungs- und TOP-Daten werden TOP-Liste fuer Einladung, Mailtext, Protokollvorlage und Dokumente erzeugt.

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

## Repository und gemeinsamer Arbeitsablauf

- Dieses Verzeichnis ist ein eigenstaendiges Git-Repository fuer die BR-App `brtop`.
- Andere eigene Nextcloud-Apps, zum Beispiel `adplaner`, leben in eigenen Repositories.
- Diese Datei und lokal referenzierte Skills bilden bei einem direkten Start in diesem Repository die vollständige Repository-Steuerung.
- Fuer Git-, Sandbox-, DDEV-/`occ`-Sicherheit, Verifikation und Learning Candidates gilt der lokal mitgefuehrte Skill `work-in-nextcloud-app`; die folgenden BRTop-Regeln und Pruefungen ergaenzen ihn.

## DDEV

Die gemeinsame Nextcloud-DDEV-Umgebung wird aus dem dokumentierten
Parent-Unterverzeichnis `nextcloud-dev` gesteuert. Bei einem eigenständigen
Checkout ist der lokale DDEV-Pfad zuerst anhand der realen Umgebung zu
ermitteln.

BRTop nutzt gemeinsame Basisbausteine aus der Hilfsapp `localbase`. In der lokalen Nextcloud muss `localbase` aktiviert sein, bevor BRTop vollstaendig lauffaehig ist.

Der versionierte BR-Gruppenvertrag in LocalBase ist die einzige Laufzeitquelle
für Mitglieder-, Vorsitz- und Stellvertretungsgruppe. Vorsitzende und
Stellvertretungen müssen zugleich Mitglieder der konfigurierten
Mitgliedergruppe sein; fehlende, beschädigte oder widersprüchliche Verträge
werden serverseitig abgewiesen. Der frühere BRTop-Wert `member_group_name`
wird nur einmalig migriert und danach nicht mehr als Einstellung angeboten.
Neue lokale BRTop-Demokonten verwenden `Benutzername = Passwort`.

Wichtige Pruefungen:

    ddev exec -d /var/www/html/html php occ app:list | grep -i localbase
    ddev exec -d /var/www/html/html php occ status
    ddev exec -d /var/www/html/html php occ app:list
    ddev exec -d /var/www/html/html php occ upgrade

## Architekturregeln

- Der lokale Skill `work-in-nextcloud-app` ist die kanonische Quelle für
  gemeinsame Schichtungs-, Modell-, Sicherheits-, UI- und Testregeln.
- Sitzungsprozess, Agenda, Ladungssnapshot, Protokollinhalt,
  Beschlussdokumente, Dokumentmetadaten und Dateiablage bleiben getrennte
  fachliche Verantwortungen.
- Refactorings berücksichtigen zuerst Prozessmodell, Sitzungstypen,
  Konfiguration, Agenda-Templates, Ladungssnapshots und Dokumentmetadaten.
- App-spezifische Dokument- und BR-Fachlogik bleibt in BRTop; gemeinsame
  Bausteine wandern erst bei mindestens zwei semantisch gleichen, testbaren
  Nutzungen nach LocalBase.
- Der öffentliche Privacy-V1-Provider projiziert nur explizit per
  Nextcloud-UID zuordenbare Metadaten. Datei- und Anhangpfade, Dateiinhalte,
  TOP-, Protokoll- und Beschlussinhalte sowie unsichere Freitexttreffer sind
  vorerst ausgeschlossen und werden als Vollständigkeitseinschränkung
  ausgewiesen. Eine spätere sichere Inhaltslösung bleibt möglich.

## Verbindliche Suite-Navigation

- BRTop besitzt keinen eigenen Nextcloud-Hauptnavigationseintrag. `orgsuite` stellt den gemeinsamen Einstieg `BR` bereit.
- Das Template bindet das zentrale OrgSuite-Menue mit `data-suite="br"` und `data-current-app="brtop"` ein.
- BR- und Sitzungsrechte bleiben ausschliesslich serverseitig im BRTop; Menuesichtbarkeit ist keine Berechtigung.
- Native Nextcloud-Administration erteilt keinen fachlichen BRTop-Vollzugriff. Er setzt pro Administrationskonto eine aktive, app-lokale Freigabe von höchstens 24 Stunden voraus; Beginn, geplantes Ende und Widerruf bleiben historisch protokolliert.
- Änderungen an Freigabehistorie oder BRTop-Rechten werden gleichzeitig im PersonalDataProvider und PermissionProvider nachgeführt.


## Tests

Vor groesseren Refactorings zuerst Charakterisierungstests fuer das bestehende gewuenschte Verhalten schreiben oder aktualisieren.

- Tests sind Teil der Architekturarbeit und kein optionaler Nachtrag. Neue oder refaktorierte BRTop-Fachlogik bekommt passende Charakterisierungs-, Unit-, Contract- oder Smoke-Tests, bevor darauf weiter aufgebaut wird.
- Schnelle PHP-Suite: `php tests/run.php`
- Schnelle JavaScript-Suite: `node tests/run-js.mjs`
- Nach LocalBase-Aenderungen mindestens die betroffenen BRTop-Smoke-/Contract-Tests laufen lassen.
- Gemeinsame LocalBase-Test-Helper nutzen, wenn dadurch echte Setup-Duplizierung verschwindet, ohne die Lesbarkeit des einzelnen Tests zu verschlechtern.
- Bei Controller-, DI-, Migrations- oder Nextcloud-Container-Aenderungen zusaetzlich gezielte DDEV-/`occ`-Checks ausfuehren.

Wichtige lokale Pruefungen:

    php tests/run.php
    node tests/run-js.mjs

Einzelne Checks, die durch die Testlaeufer gebuendelt werden:

    find js -name '*.js' -print0 | xargs -0 -n1 node --check
    node tests/js/model-smoke.js
    node tests/js/meeting-repository-smoke.js

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

## Parent-Governance-Vertrag: 1

- Die für dieses Subrepository anwendbaren Regeln des Parent-Workspaces sind
  verbindlich. Dazu gehören insbesondere app-übergreifende ADRs und
  öffentliche Verträge, Repositorygrenzen sowie Workspace-, Delivery- und
  Release-Gates.
- Diese lokale `AGENTS.md` und die lokalen Skills bleiben die vollständige,
  ohne Parent-Checkout arbeitsfähige Repository-Steuerung. Die anwendbaren
  Parent-Regeln werden dafür hier oder in den lokalen Skills mitgeführt.
- Repository-lokale Regeln dürfen Parent-Verträge konkretisieren und verschärfen,
  aber nicht abschwächen oder umgehen.
- Bei einem Widerspruch gilt bis zur Klärung die strengere Regel. Die Arbeit
  stoppt, bis die kanonische Quelle bestimmt, die Regelprojektionen
  synchronisiert und eine erforderliche Entscheidung dokumentiert ist.
- Ist der Parent-Workspace nicht verfügbar, bleibt die lokale Steuerung
  wirksam. Vor Cross-App-, Release- oder Delivery-Arbeit muss ein vermuteter
  neuerer Parent-Stand oder eine Regelungslücke zuerst gegen den Parent
  geprüft werden.
