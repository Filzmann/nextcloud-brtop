<?php

declare(strict_types=1);

namespace OCP {
    if (!interface_exists(IRequest::class)) { interface IRequest {} }
    if (!interface_exists(IUserSession::class)) { interface IUserSession { public function getUser(); } }
    if (!interface_exists(IGroupManager::class)) { interface IGroupManager { public function isAdmin($userId); public function isInGroup($userId, $group); } }
}
namespace OCP\AppFramework {
    if (!class_exists(Controller::class)) { class Controller { public function __construct(string $appName = "", mixed $request = null) {} } }
    if (!class_exists(Middleware::class)) { abstract class Middleware {} }
    if (!class_exists(Http::class)) { class Http { public const STATUS_FORBIDDEN = 403; public const STATUS_BAD_REQUEST = 400; public const STATUS_NOT_FOUND = 404; public const STATUS_INTERNAL_SERVER_ERROR = 500; } }
}
namespace OCP\AppFramework\Http {
    if (!class_exists(Response::class)) { class Response { protected int $status = 200; public function setStatus(int $status): void { $this->status = $status; } public function getStatus(): int { return $this->status; } } }
    if (!class_exists(DataResponse::class)) { class DataResponse extends Response { public function __construct(private mixed $data = [], int $status = 200) { $this->status = $status; } public function getData(): mixed { return $this->data; } } }
    if (!class_exists(TemplateResponse::class)) { class TemplateResponse extends Response { public function __construct(...$args) {} } }
}
namespace OCP\AppFramework\Http\Attribute {
    if (!class_exists(NoAdminRequired::class)) { #[\Attribute(\Attribute::TARGET_METHOD)] class NoAdminRequired {} }
    if (!class_exists(NoCSRFRequired::class)) { #[\Attribute(\Attribute::TARGET_METHOD)] class NoCSRFRequired {} }
}

namespace OCA\BrTop\AppInfo {
    if (!class_exists(Application::class, false)) {
        final class Application { public const APP_ID = 'brtop'; }
    }
}

namespace OCA\BrTop\Tests {

    use OCA\BrTop\Controller\ApiController;
    use OCA\BrTop\Exception\AccessDeniedException;
    use OCA\BrTop\Middleware\BrAccessMiddleware;
    use OCA\BrTop\Service\BrAccessService;
    use OCA\BrTop\Service\BrGroupsService;
    use OCA\BrTop\Service\TemporaryAdminAccessChecker;
    use OCP\IGroupManager;
    use OCP\IUserSession;

    $currentUid = 'other-user';
    $session = new class($currentUid) implements IUserSession {
        public string $uid;
        public function __construct(string &$uid) { $this->uid =& $uid; }
        public function getUser(): object { return new class($this->uid) { public function __construct(private string $uid) {} public function getUID(): string { return $this->uid; } }; }
    };
    $groups = new class implements IGroupManager {
        public function isAdmin($userId): bool { return $userId === 'admin'; }
        public function isInGroup($userId, $group): bool { return $userId === 'br-member' && $group === 'Betriebsrat'; }
    };
    $groupNames = new class extends BrGroupsService {
        public function __construct() {}
        public function memberGroupName(): string { return 'Betriebsrat'; }
    };
    $adminAccess = new class implements TemporaryAdminAccessChecker { public function hasActiveGrant(string $uid): bool { return $uid === 'admin'; } };
    $middleware = new BrAccessMiddleware($session, new BrAccessService($groups, $groupNames, $adminAccess));
    $controller = (new \ReflectionClass(ApiController::class))->newInstanceWithoutConstructor();

    $denied = assertThrows(
        static fn() => $middleware->beforeController($controller, 'state'),
        AccessDeniedException::class,
        'A direct API call from an unrelated user must be denied.'
    );
    $response = $middleware->afterException($controller, 'state', $denied);
    assertSameValue(403, $response->getStatus(), 'Denied API calls must return HTTP 403.');

    $currentUid = 'br-member';
    $middleware->beforeController($controller, 'state');
    assertThrows(
        static fn() => $middleware->beforeController($controller, 'saveLegislature'),
        AccessDeniedException::class,
        'A regular BR member must not manage legislature data.'
    );

    $currentUid = 'admin';
    $middleware->beforeController($controller, 'saveLegislature');

    echo 'BRTop access middleware smoke tests passed' . PHP_EOL;
}
