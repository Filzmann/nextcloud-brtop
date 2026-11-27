<?php
declare(strict_types=1);
namespace OCA\BrTop\Permission;
use OCA\FlzPermissionMatrix\PublicApi\V1\{PermissionCondition,PermissionProvider,PermissionProviderDescriptor,PermissionProviderResult,PermissionRule};
use OCP\IL10N;
final class BrTopPermissionProvider implements PermissionProvider{
 public function __construct(private BrTopPermissionSourceInterface $source, private ?IL10N $l10n = null){}

    /** Lokalisierung fachlicher Texte; im Test ohne IL10N identity. */
    private function t(string $text): string
    {
        return $this->l10n?->t($text) ?? $text;
    }
 public function descriptor():PermissionProviderDescriptor{return new PermissionProviderDescriptor('brtop','BRTop','1.0',['permissions']);}
 public function collect():PermissionProviderResult{$memberId=$this->source->memberGroupId();$chairId=$this->source->chairGroupId();$deputyId=$this->source->deputyGroupId();if($memberId===null||$chairId===null||$deputyId===null)return new PermissionProviderResult([],false,[$this->t('Der kanonische LocalBase-BR-V1-Vertrag ist nicht gültig verfügbar; BRTop verweigert fachliche Rechte.')]);$member=PermissionCondition::group($memberId);$chair=PermissionCondition::group($chairId);$admin=PermissionCondition::all([PermissionCondition::nextcloudAdmin(),PermissionCondition::temporaryAppAdminGrant()]);$rules=[
  $this->rule('Sitzungsprozess',$this->t('Alle BR-Sitzungen'),$this->t('Sitzungen und zugehörige Inhalte lesen'),'brtop.session.read',$this->t('Sitzungen lesen'),'all-sessions',$member),
  $this->rule('Sitzungsprozess',$this->t('Alle BR-Sitzungen'),$this->t('Sitzungen, TOPs und Protokollinhalte bearbeiten'),'brtop.session.manage',$this->t('Sitzungsprozess bearbeiten'),'all-sessions',$member),
  $this->rule($this->t('Dokument'),$this->t('Ladungen, Protokolle und Beschlüsse'),$this->t('Dokumente aus freigegebenen Fachdaten erzeugen und abrufen; Dateiinhalte werden vom Provider nicht untersucht'),'brtop.document.generate',$this->t('Dokumente erzeugen'),'generated-documents',$member),
  $this->rule('Administration',$this->t('BRTop-Konfiguration, Dokumentstammdaten, Vorlagen und Demo'),$this->t('Konfiguration und versionierte Vorlagen verwalten'),'brtop.settings.manage',$this->t('Konfiguration verwalten'),'app-settings',$member,'brtop:BrAccessService'),
  $this->rule('Legislatur','Legislaturverwaltung',$this->t('Anlegen und aktivieren'),'brtop.legislature.manage',$this->t('Legislatur verwalten'),'legislature',$member),
  $this->rule('Abwesenheit',$this->t('Bestätigte Abwesenheiten'),$this->t('Vorschläge prüfen und Bestätigungen speichern'),'brtop.absence.confirm',$this->t('Abwesenheiten bestätigen'),'meeting-absence',$member),
  $this->rule('Administration',$this->t('Zeitlich begrenzter Admin-Vollzugriff'),$this->t('Historie lesen sowie Freigaben für aktuelle native Administrationskonten erteilen oder widerrufen'),'brtop.admin_access.manage',$this->t('Admin-Vollzugriff verwalten'),'all',$chair,'brtop:TemporaryAdminAccessService'),
  $this->rule('Administration','BR-Datenschutzberechtigung',$this->t('Die persönliche BR-Datenschutzberechtigung ausschließlich aktuellen BR-Mitgliedern zuweisen oder entziehen'),'brtop.br_dpo.assign',$this->t('BR-Datenschutzberechtigung verwalten'),'app-settings',$chair,'brtop:BrDpoAssignmentService'),
 ];foreach([['brtop.session.read',$this->t('Sitzungen lesen'),'all-sessions'],['brtop.session.manage',$this->t('Sitzungsprozess bearbeiten'),'all-sessions'],['brtop.document.generate',$this->t('Dokumente erzeugen'),'generated-documents'],['brtop.settings.manage',$this->t('Konfiguration verwalten'),'app-settings'],['brtop.legislature.manage',$this->t('Legislatur verwalten'),'legislature'],['brtop.absence.confirm',$this->t('Abwesenheiten bestätigen'),'meeting-absence']]as[$key,$label,$scope])$rules[]=$this->rule($this->t('Temporäre Administration'),$label,$this->t('Nur native Nextcloud-Administration mit aktiver app-lokaler Freigabe'),$key,$label,$scope,$admin,'brtop:BrAccessService');return new PermissionProviderResult($rules,false,[$this->t('Persönliche app-lokale BR-Datenschutzzuweisungen sind im Matrix-V1-Bedingungsmodell nicht vollständig darstellbar; der BR-Vorsitz ist vollständig projiziert.')]);}
 private function rule(string $type,string $name,string $detail,string $key,string $label,string $scope,PermissionCondition $condition,string $source='brtop:BrAccessMiddleware'):PermissionRule{return new PermissionRule($type,$name,$detail,$key,$label,'allow',$scope,$condition,$source,'high');}
}
