# Architektur – BRTop

## Verantwortung

BRTop ist die kanonische Quelle für Sitzungen, Tagesordnungspunkte,
Ladungssnapshots, Protokollinhalte, Beschlüsse und die daraus erzeugten
Dokumente. OrgSuite stellt nur die BR-Navigation bereit; LocalBase liefert
gemeinsame, versionierte Basisverträge.

## Schichten und Daten

- Controller koordinieren Requests; Fachlogik liegt in Services,
  Datenzugriff in Repositorys beziehungsweise Stores und Darstellung in
  Templates und JavaScript-Komponenten.
- Agenda, Ladungssnapshot, Protokoll, Beschluss und Dokumentmetadaten bleiben
  getrennte Verantwortungen.
- Einladungen speichern einen unveränderlichen Snapshot der geladenen
  Mitglieder. Spätere Gruppenänderungen verändern alte Einladungen nicht.
- Dokumente werden aus den kanonischen Sitzungs- und TOP-Daten abgeleitet;
  erzeugte Dateien bilden keine zweite Fachwahrheit.

## Rechte und Integrationen

Der LocalBase-BR-Gruppenvertrag ist die einzige Laufzeitquelle für Mitglieder,
Vorsitz und Stellvertretung. Rechte werden serverseitig geprüft; Navigation
erteilt keine Rechte. Native Nextcloud-Administration benötigt für
fachlichen Vollzugriff eine app-lokale, zeitlich begrenzte Freigabe.

Datenschutz- und Berechtigungsprovider projizieren nur ausdrücklich
zuordenbare app-eigene Informationen. Datei- und Dokumentinhalte werden ohne
gesonderten sicheren Vertrag nicht als Volltext ausgewertet.
