# Architektur – BRTop

## Verantwortung

BRTop ist die kanonische Quelle für Sitzungen, Tagesordnungspunkte,
Ladungssnapshots, Protokollinhalte, Beschlüsse und die daraus erzeugten
Dokumente. OrgSuite stellt nur die BR-Navigation bereit; LocalBase liefert
gemeinsame, versionierte Basisverträge.
Der Betriebsrat ist fachlicher Owner dieser Verarbeitungen;
`Datenschutzbeauftragte` verantwortet davon getrennt Retention-Policies,
zweckgebundene Sperren und die Adminfreigabehistorie.

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
Ausschließlich Mitglieder der nativen Gruppe `Datenschutzbeauftragte` dürfen
die Freigabehistorie lesen und Freigaben für aktuelle native
Administrationskonten in der BRTop-Fachoberfläche erteilen oder widerrufen.
Die authentifizierten Endpunkte sind deshalb nicht an die native Adminroute
gebunden; Schreibrequests bleiben CSRF-geschützt und prüfen Rolle, Zielkonto
und Dauer serverseitig. Rollenverlust, ungültige Ziele, Persistenzfehler,
Ablauf und Verlust des nativen Adminstatus verweigern ohne zusätzliche
Fachrechte. Ein fehlender Vollzugriff wird ausschließlich dem betroffenen
Administrationskonto angezeigt; der direkte Sprung zur Steuerung erscheint
nur beim selben Konto mit zusätzlicher Datenschutzrolle.

Datenschutz- und Berechtigungsprovider projizieren nur ausdrücklich
zuordenbare app-eigene Informationen. Datei- und Dokumentinhalte werden ohne
gesonderten sicheren Vertrag nicht als Volltext ausgewertet.

## Processing-Metadaten

`resources/privacy-processing.json` ist die einzige app-eigene Policyquelle
für `council_legislature_and_roster_management`,
`meeting_agenda_and_protocol_management`,
`invitation_snapshot_and_absence_management`,
`document_generation_and_file_storage` und `temporary_admin_full_access`.
Der öffentliche V1-Provider von `filzmann_data_protection` lädt diesen Katalog
lazy und veröffentlicht keine personenbezogenen Laufzeitdaten.

Die Trennung folgt den vorhandenen Verantwortungsgrenzen: Legislatur und
Besetzung liefern die personelle Basis; Sitzungs-, Agenda- und
Protokolltabellen halten die kanonischen Gremieninhalte; Ladungssnapshots und
Abwesenheitsprüfung dokumentieren den konkreten Empfängerkreis; abgeleitete
Dokumente werden im persönlichen Nextcloud-Dateibereich gespeichert und sind
keine zweite Fachwahrheit. Der bestehende temporäre Admin-Vollzugriff bleibt
als eigene, höchstens 24 Stunden wirksame Verarbeitung sichtbar.

Die bestehende Art.-15-Projektion bleibt bewusst `partial`: Unsichere
Freitexttreffer, Datei- und Anhangpfade sowie Gremien- und Dateiinhalte werden
nicht automatisch ausgegeben. Der Katalog hält die beschlossenen Fristen für
Protokolle, Beschlüsse, Anwesenheitsnachweise, Ladungen, Entwürfe und
abgeleitete Dokumente sowie die sechsmonatige Adminhistorie. Ungeklärt
bleiben insbesondere Rechtsgrundlagen, Empfängerscopes, Backup/Restore,
Kalenderinfrastruktur, Shares, Spezialfristen sensibler Inhalte und der
getestete konsistente Datei-/Metadaten-Löschweg; bis dahin erfolgt keine
automatische Retention-Ausführung.
