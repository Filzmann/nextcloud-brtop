# Roadmap – BRTop

Diese Datei enthält ausschließlich offene Arbeit, zurückgestellte Vorhaben
und Freigabegates. Der aktuelle Funktionsumfang steht in `README.md`,
erledigte Änderungen in `CHANGELOG.md` und geltende Architektur in
`docs/architecture.md`.

## Nextcloud-Kompatibilitätsgate

### BRT-NC-COMPAT – RC-Kompatibilität und Zukunftsobergrenze nachweisen

Die `min-version` muss beim Release Candidate die aktuelle, autoritativ
ermittelte openDesk-Nextcloud-Hauptversion abdecken. Erst beim Erstellen eines
veröffentlichungsfähigen RC werden alle deklarierten Majors lückenlos geprüft:
Fresh Install/Upgrade, DI, Migrationen, Jobs, Sitzungs-/Dokumentpfad,
Exportdateien, Assets und sichtbare Oberfläche. `max-version` folgt ausschließlich
der höchsten lückenlos nachgewiesenen Major aus offiziellen, gepinnten
Nextcloud-Git-Quellen; eine offiziell benannte und testbare künftige Major (z. B.
NC36) wird dabei geprüft. Der regelmäßige Check der neuesten veröffentlichten
Entwicklungsruntime ist davon getrennt und ersetzt keinen RC-Nachweis.

## Freigegebene Umsetzungsaufgaben

### BRT-DOCUMENT-CONFIG – Stammdaten und Dokumentvorlagen versionieren

Status: bereit nach fachlicher Trennung fester und editierbarer Inhalte

- Gremienname, Anschrift, Ausstellungsort, Ausschusskataloge und
  organisationsspezifische Texte in validierte App-Administration überführen.
- Rechtlich beziehungsweise fachlich unveränderbare Semantik getrennt halten
  und vor unbeabsichtigter Entfernung durch Vorlagen schützen.
- ODT-, Markdown-, Einladungs-, Protokoll- und Beschlussvorlagen versionieren;
  nur validierte, nicht ausführbare Platzhalter zulassen.
- Vorschau, Freigabestatus und Rückfall auf die letzte freigegebene Version
  anbieten. Erzeugte Dokumente speichern die verwendete Vorlagenversion und
  werden später nicht umgedeutet.
- Bestehende Werte als kompatible Defaults sowie Validierung,
  Platzhalterescaping, historische Reproduktion, ungültige Vorlage und
  Rückfall testen.

## Aktueller Fokus

- Die manuellen Prüfungen werden im ausfüllbaren
  [`docs/manual-acceptance.md`](docs/manual-acceptance.md) dokumentiert.
- Den bestehenden Sitzungs-, Agenda- und Dokumentprozess fachlich
  charakterisieren und auf einem realitätsnahen Staging abnehmen.
- Vor produktiver Nutzung ein vollständiges Rollen-, Rechte-, Datenschutz-
  und Aufbewahrungskonzept festlegen und serverseitig absichern.
- Bestehende Dokumentvorlagen, Organisationsstammdaten und Ausschusskataloge
  auf fachlich feste und organisationsspezifische Inhalte prüfen.

## Geplante Erweiterungen

- Ersatzmitglied- und Nachladungslogik.
- Import oder read-only Integration von Urlaubs- und Krankmeldungen erst nach
  geklärtem Datenschutz- und Rechtevertrag.
- Direkter E-Mail-Versand mit konfigurierbarer Absenderadresse.
- PDF-/DOCX-Ausgabe zusätzlich zu den vorhandenen Dokumentwegen.
- Eigene Dokumentübersicht mit Datenbankmetadaten für Einladungen,
  Protokolle und Beschlussdokumente.
- Konfigurierbare Agenda-Templates, Sitzungstypen und Ausschusskataloge,
  soweit der aktuelle Vertrag dies noch nicht vollständig abbildet.

## Vor der Umsetzung zu klären

- Zusammensetzung des Gremiums, Listen, Ersatzmitglieder und Reihenfolge der
  Nachladung.
- Rechte für Vorsitz, Stellvertretung, Schriftführung, Ausschüsse und normale
  BR-Mitglieder.
- Rechtsverbindlichkeit, Versionierung, Korrektur und Aufbewahrung erzeugter
  Dokumente.
- Ablageort, Dateirechte, Lösch- und Rückbauvertrag.
- E-Mail-Empfänger*innen, Absender, Versandzeitpunkt und Nachweis.

## Bewusst zurückgestellt – niedrigste Priorität

### BRT-L10N – Oberfläche und Dokumentausgabe lokalisieren

Status seit 17. September 2026: Die Umsetzung beginnt erst nach allen höher
priorisierten Roadmap-Aufgaben und einer erneuten ausdrücklichen Freigabe des
Root-Vorhabens `ZM-06`. Neue Funktionen und Codeänderungen berücksichtigen
die spätere Lokalisierbarkeit an den jeweils berührten Stellen, lösen aber
keine flächige Umstellung oder Übersetzungsimplementierung aus.

Bei der späteren Umsetzung folgen Oberfläche und Meldungen der persönlichen
Nextcloud-Locale. Für Einladungen, E-Mails und Dokumente wird app-lokal eine
reproduzierbare persönliche oder organisationsweite Dokumentsprache
entschieden und mit der Vorlagenversion gespeichert; technische TOP-Arten,
Status, Rechtsgrundlagen, IDs und Freitexte bleiben unverändert.
