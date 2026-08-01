# Manuelles Abnahmeformular – BRTop

Dieses Formular dokumentiert die fachliche und visuelle Abnahme des aktuellen
BRTop-Entwicklungsstands auf einem realitätsnahen Staging-System. Eine
erfolgreiche Abnahme bestätigt weder Produktivreife noch Rechtssicherheit;
die im README genannten Einschränkungen bleiben bestehen. Pro Prüffall wird
genau ein Ergebnis markiert und eine Abweichung knapp begründet.

Keine personenbezogenen Echtdaten, vertraulichen Gremieninhalte,
Dateipfade, Tokens oder Zugangsdaten eintragen. Ausschließlich neutrale
Testkonten, synthetische Sitzungen und dafür vorgesehene Testdateien verwenden.

## Kopfdaten

| Feld | Eintrag |
|---|---|
| Datum und Uhrzeit | |
| Prüfer*in | |
| Umgebung und URL | |
| BRTop-Version | |
| Nextcloud-Version | |
| Browser und Version | |
| Fenstergröße / Zoom | |
| Neutrale Testkonten und Gruppen | |
| Verwendeter synthetischer Sitzungsfall | |

Ergebniskennzeichnung: `[ ] erfolgreich` / `[ ] nicht erfolgreich` /
`[ ] nicht geprüft`. Bei „nicht erfolgreich“ oder „nicht geprüft“ ist eine
Begründung verpflichtend.

## A. Einstieg, Zugriff und Bedienbarkeit

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| A1 | Zugriff für BR-Mitglied | Mit einem neutralen Mitglied der Gruppe `Betriebsrat` BRTop über den BR-Einstieg öffnen. | Sitzungsübersicht und erlaubte Funktionen werden geladen. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A2 | Zugriff ohne BR-Mitgliedschaft | Mit einem angemeldeten Testkonto ohne BR-Mitgliedschaft die App und einen direkten API-Aufruf versuchen. | Oberfläche und API verweigern den Zugriff serverseitig; die Menüsichtbarkeit erteilt kein Recht. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A3 | Adminabgrenzung | Eine als admin-only ausgewiesene Funktion nacheinander mit BR-Mitglied und Nextcloud-Admin versuchen. | Nur der Admin kann die Funktion ausführen; ein abgewiesener Versuch verändert nichts. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A4 | BR-Suite-Navigation | Zwischen BRTop und den weiteren aktivierten BR-Apps wechseln. | Der gemeinsame BR-Einstieg markiert BRTop korrekt; die Fachansicht bleibt erreichbar und bedienbar. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A5 | Tastatur, Fokus und Scrollen | Sitzungsübersicht, Detailansicht, Formulare und Dokumentdialog nur mit Tastatur bedienen; kleines Browserfenster verwenden. | Alle Aktionen sind erreichbar, Fokus ist sichtbar, Dialoge erzeugen keine Tastaturfalle und Inhalte bleiben scrollbar. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## B. Sitzung und Tagesordnung

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| B1 | Nächste reguläre Sitzung | Mit neutralen Einstellungen „nächste Sitzung planen“ ausführen und denselben Vorgang erneut versuchen. | Eine passende nächste Sitzung wird angelegt; die Wiederholung erzeugt keinen widersprüchlichen Doppelstand. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B2 | Sitzungstypen | Je eine synthetische reguläre Sitzung und einen weiteren angebotenen Sitzungstyp anlegen und wieder öffnen. | Typ, Datum, Titel und zugehörige Detailansicht bleiben korrekt erhalten. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B3 | Standardagenda | Eine reguläre Sitzung öffnen. | Die fachliche Grundstruktur von Protokollen bis zu weiteren Tagesordnungspunkten erscheint in der vorgesehenen Reihenfolge. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B4 | TOP-Arten und Personalfälle | Neutrale Beratungs-, Berichts- und Gliederungs-TOPs sowie je einen Fall nach § 99, § 100 und § 102 BetrVG anlegen. | Einordnung, Rechtsgrundlage und unterschiedliche Darstellung bleiben fachlich nachvollziehbar. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B5 | TOP-Hierarchie | TOPs bis zur dritten Ebene verschieben, ein- und ausrücken, umbenennen und einen entbehrlichen Test-TOP löschen. | Reihenfolge und maximale Tiefe bleiben konsistent; die Ansicht zeigt den gespeicherten Baum korrekt. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B6 | Mehrere Beschlüsse | Einen beschlussrelevanten Test-TOP mit zwei getrennten Beschlussfragen anlegen. | Beide Beschlüsse bleiben fachlich getrennt und können später als einzelne Beschlussdokumente erzeugt werden. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## C. Legislatur, Ladung und Protokoll

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| C1 | Legislaturentwurf und Aktivierung | Eine ausschließlich synthetische Legislatur mit Testlisten und Testmitgliedern als Entwurf speichern und aktivieren. | Der Entwurf ist bearbeitbar; die aktivierte Legislatur wird versiegelt und eindeutig verwendet. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| C2 | Verhinderungen | Für eine Testsitzung vorgeschlagene Verhinderungen prüfen, ändern und verbindlich bestätigen. | Auswahl und Bestätigung sind nachvollziehbar; nach der Bestätigung bleibt der Stand stabil. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| C3 | Ladungssnapshot | Eine Einladung erzeugen, danach die synthetische Gremienbesetzung ändern und die bestehende Einladung erneut prüfen. | Die bereits erzeugte Ladung behält ihren ursprünglichen Empfänger-Snapshot. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| C4 | Getrennte Personalfälle | Einladung und Protokoll für die Testfälle nach § 99, § 100 und § 102 erzeugen. | Die Einladung darf kompakt zusammenfassen; im Protokoll bleiben die Fälle getrennt aufgeführt. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| C5 | Protokollbearbeitung | Mehrere Protokollblöcke bearbeiten, speichern, neu laden und einen Fehlerfall durch ungültige Eingabe auslösen. | Gespeicherte Inhalte bleiben erhalten; Fehler werden verständlich angezeigt und überschreiben keinen gültigen Stand. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## D. Dokumente und Abgrenzung des Entwicklungsstands

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| D1 | Einladung und Protokoll | Für die synthetische Sitzung Einladung und Protokoll erzeugen und über die Dokumentansicht öffnen. | Beide Dokumente werden aus dem gespeicherten Sitzungs- und TOP-Stand erzeugt und sind lesbar. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| D2 | Einzelne Beschlussdokumente | Für B6 Beschlussdokumente erzeugen. | Für jede Beschlussfrage entsteht ein eigenes Dokument; Inhalte anderer Beschlüsse werden nicht vermischt. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| D3 | Vorgesehene Dateiablage | Die ausschließlich für die Abnahme erzeugten Dateien im Nextcloud-Dateibereich prüfen. | Dateien liegen im vorgesehenen BR-Sitzungsbereich; fremde Dateien wurden nicht verändert. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| D4 | Nicht vorhandene Produktfunktionen | README-Einschränkungen mit der sichtbaren Oberfläche vergleichen. | Nicht umgesetzte Ersatzmitglied-, E-Mail-, PDF-/DOCX- und Produktivrechte-Funktionen werden nicht fälschlich als abgenommen dokumentiert. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| D5 | Datensparsame Abnahmeunterlagen | Formular, Screenshots und erzeugte Testdokumente auf Inhalte prüfen. | Es wurden nur synthetische Daten dokumentiert; keine vertraulichen Inhalte, Zugangsdaten oder unnötigen Pfade sind enthalten. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## Abschlussentscheidung

| Feld | Eintrag |
|---|---|
| Anzahl erfolgreich | |
| Anzahl nicht erfolgreich | |
| Anzahl nicht geprüft | |
| Kritische Abweichungen / Ticketreferenzen | |
| Erneute Prüfung erforderlich bis | |
| Entscheidung zum aktuellen Entwicklungsstand | [ ] abgenommen [ ] mit Auflagen abgenommen [ ] nicht abgenommen |
| Produktiv- oder Rechtssicherheitsfreigabe | nicht Bestandteil dieses Formulars |
| Begründung der Gesamtentscheidung | |
| Name / Datum | |
