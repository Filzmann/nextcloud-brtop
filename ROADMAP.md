# Roadmap – BRTop

Diese Datei enthält ausschließlich zukünftige Ziele und offene
Produktentscheidungen. Geltende Fach-, Rechte-, Sicherheits- und
Architekturregeln stehen in `AGENTS.md`.

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

## Zukunftsplanung – nicht freigegeben

### BRT-L10N – BRTop-Oberfläche und Dokumentausgabe lokalisieren

Status: später, nicht freigegeben; Dokumentsprache, Pilot-App, Reihenfolge und
Rohtext-Gate werden vor jeder Umsetzung appübergreifend separat freigegeben

- Oberfläche und nutzerbezogene Meldungen nach persönlicher
  Nextcloud-Locale lokalisieren.
- Für Einladungen, E-Mails und Dokumente vorab festlegen, ob persönliche oder
  organisationsweit konfigurierte Dokumentsprache gilt; die verwendete
  Locale mit der Vorlagenversion reproduzierbar halten.
- Technische TOP-Arten, Status, Rechtsgrundlagen, IDs und gespeicherte
  Freitexte unverändert lassen.
- Deutsche Ausgabe, eine weitere Locale, Fallback, Pluralformen,
  Platzhalter, Escaping und Dokumentreproduktion testen.

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
