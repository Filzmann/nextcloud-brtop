<?php

declare(strict_types=1);

namespace OCP {
    if (!interface_exists(IUserSession::class)) { interface IUserSession { public function getUser(); } }
}
namespace OCP\AppFramework {
    if (!class_exists(Controller::class)) { class Controller {} }
    if (!class_exists(Middleware::class)) { abstract class Middleware {} }
    if (!class_exists(Http::class)) { class Http { public const STATUS_FORBIDDEN=403; } }
}
namespace OCP\AppFramework\Http {
    if (!class_exists(DataResponse::class)) { class DataResponse {} }
    if (!class_exists(TemplateResponse::class)) { class TemplateResponse {} }
}
namespace OCA\BrTop\Controller {
    if (!class_exists(ApiController::class, false)) { class ApiController extends \OCP\AppFramework\Controller {} }
    if (!class_exists(PageController::class, false)) { class PageController extends \OCP\AppFramework\Controller {} }
}
namespace OCA\BrTop\Tests {
    use OCA\BrTop\Controller\ApiController;
    use OCA\BrTop\Middleware\BrAccessMiddleware;
    use OCA\BrTop\Service\BrAccessService;
    use OCA\BrTop\Service\BrPermissionService;

    $session = new class implements \OCP\IUserSession {
        public function getUser(): object { return new class { public function getUID(): string { return 'member'; } }; }
    };
    $access = new class extends BrAccessService {
        public array $checks = [];
        public function __construct() {}
        public function assertPermission(string $uid, string $permission): void { $this->checks[] = [$uid, $permission]; }
        public function assertCanUse(string $uid): void { throw new \RuntimeException('Unspezifischer Altzugriff wurde verwendet.'); }
        public function assertAdmin(string $uid): void { throw new \RuntimeException('Adminstatus wurde statt Fachrecht verwendet.'); }
    };
    $middleware = new BrAccessMiddleware($session, $access);
    $controller = new ApiController();
    $expected = [
        'state' => BrPermissionService::SESSION_READ,
        'createMeeting' => BrPermissionService::SESSION_MANAGE,
        'planNextRegularMeeting' => BrPermissionService::SESSION_MANAGE,
        'deleteMeeting' => BrPermissionService::SESSION_MANAGE,
        'addTop' => BrPermissionService::SESSION_MANAGE,
        'moveTop' => BrPermissionService::SESSION_MANAGE,
        'changeTopDepth' => BrPermissionService::SESSION_MANAGE,
        'updateTopSubject' => BrPermissionService::SESSION_MANAGE,
        'deleteTop' => BrPermissionService::SESSION_MANAGE,
        'addProtocolBlock' => BrPermissionService::SESSION_MANAGE,
        'updateProtocolBlock' => BrPermissionService::SESSION_MANAGE,
        'generateInvitation' => BrPermissionService::DOCUMENT_GENERATE,
        'generateProtocol' => BrPermissionService::DOCUMENT_GENERATE,
        'generateResolutions' => BrPermissionService::DOCUMENT_GENERATE,
        'updateSettings' => BrPermissionService::SETTINGS_MANAGE,
        'seedDemo' => BrPermissionService::SETTINGS_MANAGE,
        'legislature' => BrPermissionService::LEGISLATURE_MANAGE,
        'saveLegislature' => BrPermissionService::LEGISLATURE_MANAGE,
        'activateLegislature' => BrPermissionService::LEGISLATURE_MANAGE,
        'absenceSuggestions' => BrPermissionService::ABSENCE_CONFIRM,
        'saveConfirmedAbsences' => BrPermissionService::ABSENCE_CONFIRM,
    ];
    foreach ($expected as $method => $permission) {
        $middleware->beforeController($controller, $method);
        assertSameValue(['member', $permission], array_pop($access->checks), "{$method} muss das getrennte Fachrecht prüfen.");
    }

    echo "BRTop permission middleware contract tests passed\n";
}
