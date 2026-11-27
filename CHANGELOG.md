# Changelog

## Unreleased

- min-version auf Nextcloud 33 angehoben: NC 29–32 sind offiziell EOL (25.11.2026); der RC-Kompatibilitätsgate zeigte auf NC 29 eine nicht vorhandene private API (`AppManager::getEnabledApps()`).
- Zustandslose Browserbausteine aus `flz_shared` 0.1.0 werden als geprüftes,
  app-lokales Bundle unter `window.OCA.BrTop.Shared` ausgeliefert. LocalBase-
  Browserassets sind keine Laufzeitabhängigkeit.
- Browser-Consumer-Smoke ordnet die ausgeführten App-Quellen über absolute
  VM-Dateipfade der Coverage zu und prüft verweigerte, nicht als JSON gelieferte
  sowie leere API-Antworten. Die Produktivlogik bleibt unverändert.
- Getrennte BRTop-Fachrechte für Sitzungslesen, Sitzungsbearbeitung,
  Dokumenterzeugung, Einstellungen, Legislatur und Abwesenheitsbestätigung
  eingeführt. Alle aktuellen BR-Mitglieder erhalten diese Rechte als Default
  im gemeinsamen Scope aller Sitzungen; native Administration benötigt
  weiterhin eine aktive app-lokale Freigabe. Der BR-Vorsitz kann die
  Freigabesteuerung einzelnen aktuellen BR-Mitgliedern als persönliche
  BR-Datenschutzberechtigung über ein Auswahlmenü übertragen. Provider,
  Art.-15-Projektion und Verarbeitungskatalog bilden die neue Grenze ab.
- BR-Datenschutzberechtigung auf native Nextcloud-Gruppe umgestellt (SSOT): `BrDpoAssignmentService` speichert nur noch die Gruppen-ID (AppConfig `br_dpo_group`, Default `br_dpo`); `replace()` setzt/entzieht die Mitgliedschaft in der Gruppe, `isAssigned()` prüft `IGroupManager::isInGroup` plus BR-Mitgliedschaft. Die alte UID-Liste `br_dpo_permission_uids` wird nicht mehr gelesen oder geschrieben (Fresh Install/Reinstall ist der autorisierte Übergang). API-Payload (UID-Liste) und UI bleiben unverändert.
- Dokumentkonfiguration ergänzt: Organisationsstammdaten und Ausschusskataloge
  werden validiert gespeichert; Text- und Protokoll-ODT-Vorlagen können als
  Entwurf versioniert, geprüft und ausdrücklich freigegeben werden. Neue
  Dokumentmetadaten führen Vorlagentyp und Version; ungültige freigegebene
  Versionen nutzen die eingebaute Vorlage als Rückfall. Die ODT-Archivprüfung
  ist durch Tests belegt, eine vollständige Browser-/DDEV-/Nextcloud-
  Runtime-Abnahme bleibt offen.
- Nextcloud 35.0.1 durch Fresh Install, Upgrade 34→35 sowie Provider-,
  Berechtigungs-, Runtime-, UI-/API- und Asset-Smokes bestätigt. Der
  Permission-Provider-Listener erfüllt nun Nextclouds öffentlichen
  `IEventListener`-Vertrag; die Berechtigungssemantik bleibt unverändert.
  Die deklarierten Hauptversionen 29 bis 32 waren nicht Teil dieses Laufs.
- Die app-lokale Admin-Vollzugriffssteuerung aus dem technischen Adminbereich
  in die rollenabhängige BRTop-Fachoberfläche verschoben; native Administration
  allein, gewöhnliche Konten und manipulierte Ziele bleiben mutationsfrei
  abgewiesen. Sichere Hinweis- und Direktlinkregeln sowie Datenschutz- und
  Berechtigungsprojektionen wurden daran angeglichen.
- App-eigenen Processing-Metadata-Katalog für sechs BR-TOP-Verarbeitungen über
  den öffentlichen Datenschutz-V1-Vertrag veröffentlicht.
- Dokumentations- und Steuerungsstruktur vereinheitlicht.

## 0.1.34

- Bestehender BRTop-Entwicklungsstand bei Einführung dieses Changelogs.
- Frühere Einzeländerungen sind nicht nachträglich rekonstruiert; der aktuelle
  Funktionsumfang steht in `README.md`.
