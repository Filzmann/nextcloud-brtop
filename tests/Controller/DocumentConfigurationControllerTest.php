<?php

declare(strict_types=1);

namespace {
    if (!class_exists(ZipArchive::class)) {
        final class ZipArchive {
            public int $numFiles = 4;
            private array $entries = [
                ['name' => 'mimetype', 'content' => 'application/vnd.oasis.opendocument.text'],
                ['name' => 'content.xml', 'content' => '<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"><office:body><office:text>{{SITZUNG_DATUM}}</office:text></office:body></office:document-content>'],
                ['name' => 'styles.xml', 'content' => '<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>'],
                ['name' => 'META-INF/manifest.xml', 'content' => '<manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0"/>'],
            ];
            public function open(string $path): bool { return true; }
            public function close(): bool { return true; }
            public function getNameIndex(int $index): string|false { return $this->entries[$index]['name'] ?? false; }
            public function statIndex(int $index): array|false { return isset($this->entries[$index]) ? ['size' => strlen($this->entries[$index]['content'])] : false; }
            public function getFromIndex(int $index): string|false { return $this->entries[$index]['content'] ?? false; }
            public function getFromName(string $name): string|false { foreach ($this->entries as $entry) { if ($entry['name'] === $name) { return $entry['content']; } } return false; }
        }
    }
}

namespace OCP {
    if (!interface_exists(IRequest::class)) { interface IRequest {} }
    if (!interface_exists(IUserSession::class)) { interface IUserSession { public function getUser(); } }
}
namespace OCP\AppFramework {
    if (!class_exists(Controller::class)) { class Controller { public function __construct(string $appId, \OCP\IRequest $request) {} } }
    if (!class_exists(Http::class)) { final class Http { public const STATUS_BAD_REQUEST=400; public const STATUS_FORBIDDEN=403; public const STATUS_INTERNAL_SERVER_ERROR=500; } }
}
namespace OCP\AppFramework\Http {
    if (!class_exists(JSONResponse::class)) { class JSONResponse { public function __construct(private array $data=[],private int $status=200){} public function getData():array{return $this->data;} public function getStatus():int{return $this->status;} } }
}
namespace OCP\AppFramework\Http\Attribute {
    if (!class_exists(NoCSRFRequired::class)) { #[\Attribute(\Attribute::TARGET_METHOD)] final class NoCSRFRequired {} }
    if (!class_exists(NoAdminRequired::class)) { #[\Attribute(\Attribute::TARGET_METHOD)] final class NoAdminRequired {} }
}
namespace OCA\BrTop\AppInfo {
    if (!class_exists(Application::class, false)) { final class Application { public const APP_ID='brtop'; } }
}
namespace OCA\BrTop\Tests {
    use OCA\BrTop\Controller\DocumentConfigurationController;
    use OCA\BrTop\Exception\AccessDeniedException;
    use OCA\BrTop\Repository\DocumentTemplateRepositoryInterface;
    use OCA\BrTop\Service\BrAccessService;
    use OCA\BrTop\Service\BrtopLogger;
    use OCA\BrTop\Service\BrtopSettingsService;
    use OCA\BrTop\Service\DocumentTemplateEngine;
    use OCA\BrTop\Service\DocumentTemplateService;
    use OCA\BrTop\Service\OdtTemplateValidator;
    use OCP\AppFramework\Http;

    $repository = new class implements DocumentTemplateRepositoryInterface {
        public array $rows=[];
        public function createDraft(string $templateKey,string $format,string $content):array{$row=['template_key'=>$templateKey,'version_no'=>count($this->rows)+1,'format'=>$format,'content'=>$content,'status'=>'draft','created_at'=>'now','approved_at'=>null];$this->rows[]=$row;return $row;}
        public function approve(string $templateKey,int $version):array{foreach($this->rows as &$row){if($row['template_key']===$templateKey&&(int)$row['version_no']===$version){$row['status']='approved';$row['approved_at']='now';return $row;}}throw new \InvalidArgumentException('Vorlagenversion nicht gefunden.');}
        public function find(string $templateKey,int $version):?array{foreach($this->rows as $row)if($row['template_key']===$templateKey&&(int)$row['version_no']===$version)return $row;return null;}
        public function approvedDescending(string $templateKey):array{return array_values(array_filter($this->rows,static fn(array $row):bool=>$row['template_key']===$templateKey&&$row['status']==='approved'));}
        public function metadata():array{return array_map(static function(array $row):array{unset($row['content']);return $row;},$this->rows);}
    };
    $templates = new DocumentTemplateService($repository,new DocumentTemplateEngine(),new OdtTemplateValidator(),dirname(__DIR__,2).'/templates/odt/protokoll-template.odt');
    $settings = new class extends BrtopSettingsService {
        public array $saved=[];
        public function __construct() {}
        public function documentOrganization():array{return $this->saved?:['councilName'=>'Betriebsrat','councilAddress'=>'Adresse','issuePlace'=>'Berlin','committeeCodes'=>[],'invitationSalutation'=>'Hallo','invitationIntroduction'=>'Einladung','invitationClosing'=>'Gruß','invitationSender'=>'BR'];}
        public function saveDocumentOrganization(string $councilName,string $councilAddress,string $issuePlace,string $committeeCodesJson,string $invitationSalutation,string $invitationIntroduction,string $invitationClosing,string $invitationSender):array{$this->saved=['councilName'=>$councilName,'councilAddress'=>$councilAddress,'issuePlace'=>$issuePlace,'committeeCodes'=>[],'invitationSalutation'=>$invitationSalutation,'invitationIntroduction'=>$invitationIntroduction,'invitationClosing'=>$invitationClosing,'invitationSender'=>$invitationSender];return $this->saved;}
    };
    $access = new class extends BrAccessService {
        public bool $allowed=true;
        public function __construct() {}
        public function assertAdmin(string $uid):void{if(!$this->allowed)throw new AccessDeniedException('Keine Berechtigung.');}
    };
    $session = new class implements \OCP\IUserSession {
        public function getUser(){return new class {public function getUID():string{return 'admin-user';}};}
    };
    $logger = new class extends BrtopLogger { public array $errors=[]; public function __construct(){} public function error(string $action,\Throwable $exception,array $context=[]):void{$this->errors[]=$action;} };
    $controller = new DocumentConfigurationController(new class implements \OCP\IRequest {},$session,$access,$settings,$templates,$logger);

    foreach(['saveOrganization','saveDraft','approve'] as $method){
        if((new \ReflectionMethod(DocumentConfigurationController::class,$method))->getAttributes(\OCP\AppFramework\Http\Attribute\NoCSRFRequired::class)!==[])throw new \RuntimeException("{$method} darf CSRF-Schutz nicht abschalten.");
        if((new \ReflectionMethod(DocumentConfigurationController::class,$method))->getAttributes(\OCP\AppFramework\Http\Attribute\NoAdminRequired::class)!==[])throw new \RuntimeException("{$method} muss zusätzlich Nextcloud-Adminrechte verlangen.");
    }

    $access->allowed=false;
    if($controller->state()->getStatus()!==Http::STATUS_FORBIDDEN)throw new \RuntimeException('Admin-Konfiguration darf ohne aktive app-lokale Freigabe nicht gelesen werden.');
    $access->allowed=true;
    if($controller->state()->getStatus()!==200)throw new \RuntimeException('Freigegebene Administration kann den Konfigurationsstand nicht lesen.');

    $before=count($repository->rows);
    $invalid=$controller->saveDraft('protocol_markdown','{{UNKNOWN}}');
    if($invalid->getStatus()!==Http::STATUS_BAD_REQUEST||count($repository->rows)!==$before)throw new \RuntimeException('Ungültige Vorlagen müssen ohne Persistenz abgewiesen werden.');
    $draft=$controller->saveDraft('protocol_markdown',"Eigener Kopf\n{{PROTOCOL_BODY}}\n");
    if($draft->getStatus()!==200||$draft->getData()['template']['version']!==1)throw new \RuntimeException('Gültige Entwürfe werden nicht versioniert gespeichert.');
    $preview=$controller->preview('protocol_markdown',"Eigener Kopf\n{{PROTOCOL_BODY}}\n");
    if($preview->getStatus()!==200||count($repository->rows)!==1)throw new \RuntimeException('Vorschau darf keine weitere Version persistieren.');
    $approved=$controller->approve('protocol_markdown',1);
    if($approved->getStatus()!==200||$approved->getData()['template']['status']!=='approved')throw new \RuntimeException('Vorlagenfreigabe funktioniert nicht.');

    $odtBytes=(string)file_get_contents(dirname(__DIR__,2).'/templates/odt/protokoll-template.odt');
    if(preg_match('//u',$odtBytes)!==false)throw new \RuntimeException('Die Transportregression benötigt echte, nicht als UTF-8 serialisierbare ODT-Binärdaten.');
    $odtDraft=$controller->saveDraft('protocol_odt',base64_encode($odtBytes),'base64');
    $odtDraftData=$odtDraft->getData()['template']??[];
    if($odtDraft->getStatus()!==200||($odtDraftData['encoding']??'')!=='base64'||($odtDraftData['content']??'')!==base64_encode($odtBytes)||json_encode($odtDraft->getData())===false)throw new \RuntimeException('ODT-Entwürfe müssen JSON-sicher Base64-kodiert beantwortet werden.');
    $odtApproved=$controller->approve('protocol_odt',(int)$odtDraftData['version']);
    $odtApprovedData=$odtApproved->getData()['template']??[];
    if($odtApproved->getStatus()!==200||($odtApprovedData['encoding']??'')!=='base64'||($odtApprovedData['content']??'')!==base64_encode($odtBytes)||json_encode($odtApproved->getData())===false)throw new \RuntimeException('Freigegebene ODT-Vorlagen müssen JSON-sicher Base64-kodiert beantwortet werden.');

    $routes=(string)file_get_contents(dirname(__DIR__,2).'/appinfo/routes.php');
    foreach(['document_configuration#state','document_configuration#saveOrganization','document_configuration#saveDraft','document_configuration#preview','document_configuration#approve']as$route)if(!str_contains($routes,$route))throw new \RuntimeException("Admin-Konfigurationsroute fehlt: {$route}");

    echo "DocumentConfigurationController tests passed\n";
}
