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

1. Demo-Sitzung anlegen.
2. Dokumente erzeugen.
3. In den Dateien prüfen, ob unter `BR-Sitzungen/...` Markdown-Dateien erzeugt wurden.

Geplante Erweiterungen und offene Fachentscheidungen stehen in
[`ROADMAP.md`](ROADMAP.md).
