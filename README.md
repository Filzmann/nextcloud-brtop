# BRTop

Nextcloud-App-Prototyp für BR-TOPs, Ladungen, Protokollvorlagen und Beschlussdokumente.

## Status

Development-Prototyp. Nicht produktiv und nicht rechtssicher.

Noch nicht enthalten:

- automatische Ersatzmitgliedberechnung,
- geprüfte Nachladungslogik,
- Import von Urlaubs-/Krankmeldungen,
- echte E-Mail-Versendung,
- PDF/DOCX-Export,
- Rollen- und Rechtekonzept.

## Installation

Nextcloud-Root, Runtimebenutzer, `apps_paths` und CLI-PHP werden aus der realen
Zielumgebung ermittelt. Das App-Verzeichnis muss `brtop` heißen. Nach einem
Backup wird die App mit dem dort verwendeten CLI-PHP und Runtimekontext über
`occ app:enable brtop` aktiviert.

Nextcloud 34 hat keinen `occ migrations:migrate`-Befehl. App-Migrationen laufen
beim Aktivieren der App beziehungsweise über `occ upgrade`, wenn `occ status`
`needsDbUpgrade: true` meldet.

## Test

```bash
<CLI-PHP> occ app:list | grep brtop
<CLI-PHP> occ status
```

Dann in Nextcloud öffnen:

```text
/apps/brtop
```

Der separate Adminabschnitt `BR TOP` enthält den Schalter für zeitlich begrenzten fachlichen Vollzugriff. Ein Nextcloud-Administrationskonto erhält nicht automatisch Sitzungs- oder Gremienrechte. Die Freigabe erfolgt pro Administrationskonto für 1, 4, 8 oder höchstens 24 Stunden, kann vorzeitig widerrufen werden und wird app-lokal mit Beginn und Ende protokolliert. Datenschutz- und Berechtigungsprovider weisen diese Freigaben aus.

1. Demo-Sitzung anlegen.
2. Dokumente erzeugen.
3. In den Dateien prüfen, ob unter `BR-Sitzungen/...` Markdown-Dateien erzeugt wurden.

Neu angelegte lokale BRTop-Demokonten verwenden ihren Benutzernamen als
Passwort. Diese Zugangsdaten sind ausschließlich für lokale Test- und
Demoumgebungen bestimmt.

Für die fachliche, visuelle und datenschutzbezogene Staging-Prüfung steht ein
ausfüllbares [manuelles Abnahmeformular](docs/manual-acceptance.md) bereit.
Es bezieht sich ausdrücklich auf den aktuellen Entwicklungsstand und erteilt
keine Produktiv- oder Rechtssicherheitsfreigabe.

Geplante Erweiterungen und offene Fachentscheidungen stehen in
[`ROADMAP.md`](ROADMAP.md).

## Dokumentation

- [Architektur](docs/architecture.md)
- [Manuelle Abnahme](docs/manual-acceptance.md)
- [Roadmap](ROADMAP.md)
- [Changelog](CHANGELOG.md)
- [Arbeitsregeln](AGENTS.md)
