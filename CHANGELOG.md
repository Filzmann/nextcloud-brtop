# Changelog

## Unreleased

- Nextcloud 35.0.1 durch Fresh Install, Upgrade 34→35 sowie Provider-,
  Berechtigungs-, Runtime-, UI-/API- und Asset-Smokes bestätigt. Der
  Permission-Provider-Listener erfüllt nun Nextclouds öffentlichen
  `IEventListener`-Vertrag; die Berechtigungssemantik bleibt unverändert.
  Die deklarierten Hauptversionen 29 bis 32 waren nicht Teil dieses Laufs.
- Die app-lokale Admin-Vollzugriffssteuerung aus dem technischen Adminbereich
  in die rollenabhängige BRTop-Fachoberfläche verschoben. Nur
  `Datenschutzbeauftragte` können Historie lesen sowie Freigaben für aktuelle
  native Administrationskonten erteilen oder widerrufen; native Administration
  allein, gewöhnliche Konten und manipulierte Ziele bleiben mutationsfrei
  abgewiesen. Sichere Hinweis- und Direktlinkregeln sowie Datenschutz- und
  Berechtigungsprojektionen wurden daran angeglichen.
- App-eigenen Processing-Metadata-Katalog für fünf BR-TOP-Verarbeitungen über
  den öffentlichen Datenschutz-V1-Vertrag veröffentlicht.
- Dokumentations- und Steuerungsstruktur vereinheitlicht.

## 0.1.34

- Bestehender BRTop-Entwicklungsstand bei Einführung dieses Changelogs.
- Frühere Einzeländerungen sind nicht nachträglich rekonstruiert; der aktuelle
  Funktionsumfang steht in `README.md`.
