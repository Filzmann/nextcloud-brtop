<?php
declare(strict_types=1);
namespace OCA\BrTop\Permission;
use OCA\FilzmannPermissionMatrix\PublicApi\V1\{PermissionCondition,PermissionProvider,PermissionProviderDescriptor,PermissionProviderResult,PermissionRule};
final class BrTopPermissionProvider implements PermissionProvider{
 public function __construct(private BrTopPermissionSourceInterface $source){}
 public function descriptor():PermissionProviderDescriptor{return new PermissionProviderDescriptor('brtop','BRTop','1.0',['permissions']);}
 public function collect():PermissionProviderResult{$member=PermissionCondition::group($this->source->memberGroupId());$admin=PermissionCondition::all([PermissionCondition::nextcloudAdmin(),PermissionCondition::temporaryAppAdminGrant()]);return new PermissionProviderResult([
  $this->rule('Sitzungsprozess','Sitzungen, TOPs und Protokollmetadaten','Lesen und bearbeiten','brtop.session.manage','Sitzungsprozess verwalten','all-sessions',$member),
  $this->rule('Dokument','Ladungen, Protokolle und Beschlüsse','Dokumente aus freigegebenen Fachdaten erzeugen und abrufen; Dateiinhalte werden vom Provider nicht untersucht','brtop.document.generate','Dokumente erzeugen','generated-documents',$member),
  $this->rule('Sitzungsprozess','Alle Sitzungen','Adminzugriff entspricht zusätzlich dem allgemeinen Appzugriff','brtop.session.manage','Sitzungsprozess verwalten','all-sessions',$admin),
  $this->rule('Dokument','Alle Dokumente','Adminzugriff entspricht zusätzlich dem allgemeinen Appzugriff','brtop.document.generate','Dokumente erzeugen','generated-documents',$admin),
  $this->rule('Administration','BRTop-Konfiguration und Demo','Nur Nextcloud-Administration','brtop.settings.manage','Konfiguration verwalten','app-settings',$admin),
  $this->rule('Legislatur','Legislaturverwaltung','Anlegen und aktivieren','brtop.legislature.manage','Legislatur verwalten','legislature',$admin),
  $this->rule('Abwesenheit','Bestätigte Abwesenheiten','Vorschläge prüfen und Bestätigungen speichern','brtop.absence.confirm','Abwesenheiten bestätigen','meeting-absence',$admin),
 ]);}
 private function rule(string $type,string $name,string $detail,string $key,string $label,string $scope,PermissionCondition $condition):PermissionRule{return new PermissionRule($type,$name,$detail,$key,$label,'allow',$scope,$condition,'brtop:BrAccessMiddleware','high');}
}
