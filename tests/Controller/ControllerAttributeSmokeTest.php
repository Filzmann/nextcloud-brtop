<?php

declare(strict_types=1);

namespace OCP {
    if (!interface_exists(IRequest::class)) { interface IRequest {} }
    if (!interface_exists(IUserSession::class)) { interface IUserSession {} }
}
namespace OCP\AppFramework {
    if (!class_exists(Controller::class)) { class Controller { public function __construct(string $appName, \OCP\IRequest $request) {} } }
    if (!class_exists(Http::class)) { class Http { public const STATUS_BAD_REQUEST = 400; public const STATUS_NOT_FOUND = 404; public const STATUS_INTERNAL_SERVER_ERROR = 500; } }
}
namespace OCP\AppFramework\Http {
    if (!class_exists(Response::class)) { class Response {} }
    if (!class_exists(DataResponse::class)) { class DataResponse extends Response { public function __construct(mixed $data = [], int $status = 200) {} } }
    if (!class_exists(TemplateResponse::class)) { class TemplateResponse extends Response { public function __construct(string $appName, string $templateName) {} } }
}
namespace OCP\AppFramework\Http\Attribute {
    if (!class_exists(NoAdminRequired::class)) { #[\Attribute(\Attribute::TARGET_METHOD)] class NoAdminRequired {} }
    if (!class_exists(NoCSRFRequired::class)) { #[\Attribute(\Attribute::TARGET_METHOD)] class NoCSRFRequired {} }
}

namespace OCA\BrTop\AppInfo {
    if (!class_exists(Application::class, false)) {
        final class Application {
            public const APP_ID = 'brtop';
        }
    }
}

namespace {

    use OCA\BrTop\Controller\ApiController;
    use OCA\BrTop\Controller\PageController;
    use OCP\AppFramework\Http\Attribute\NoAdminRequired;
    use OCP\AppFramework\Http\Attribute\NoCSRFRequired;

    $pageIndex = new \ReflectionMethod(PageController::class, 'index');
    if ($pageIndex->getAttributes(NoCSRFRequired::class) === []) {
        throw new \RuntimeException('Page index should be loadable without a CSRF header.');
    }
    if ($pageIndex->getAttributes(NoAdminRequired::class) === []) {
        throw new \RuntimeException('Page index should be available to regular users.');
    }

    $apiActions = [
        'state',
        'createMeeting',
        'planNextRegularMeeting',
        'addTop',
        'moveTop',
        'changeTopDepth',
        'updateTopSubject',
        'deleteTop',
        'deleteMeeting',
        'addProtocolBlock',
        'updateProtocolBlock',
        'generateInvitation',
        'generateProtocol',
        'generateResolutions',
    ];

    foreach ($apiActions as $action) {
        $method = new \ReflectionMethod(ApiController::class, $action);
        if ($method->getAttributes(NoAdminRequired::class) === []) {
            throw new \RuntimeException($action . ' should be available to regular users.');
        }
        if ($method->getAttributes(NoCSRFRequired::class) !== []) {
            throw new \RuntimeException($action . ' should keep the default CSRF protection.');
        }
    }

    foreach (['updateSettings', 'seedDemo', 'legislature', 'saveLegislature', 'activateLegislature', 'absenceSuggestions', 'saveConfirmedAbsences'] as $adminAction) {
        $method = new \ReflectionMethod(ApiController::class, $adminAction);
        if ($method->getAttributes(NoAdminRequired::class) !== []) {
            throw new \RuntimeException($adminAction . ' must remain restricted to Nextcloud admins.');
        }
        if ($method->getAttributes(NoCSRFRequired::class) !== []) {
            throw new \RuntimeException($adminAction . ' should keep the default CSRF protection.');
        }
    }

    echo 'BRTop controller attribute smoke tests passed' . PHP_EOL;
}
