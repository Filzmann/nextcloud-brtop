<?php

declare(strict_types=1);

namespace {
    if (!class_exists(\OCP\AppFramework\Controller::class)) {
        eval('namespace OCP\AppFramework; class Controller { public function __construct(string $appName, \OCP\IRequest $request) {} }');
    }
    if (!interface_exists(\OCP\IRequest::class)) {
        eval('namespace OCP; interface IRequest {}');
    }
    if (!interface_exists(\OCP\IUserSession::class)) {
        eval('namespace OCP; interface IUserSession {}');
    }
    if (!class_exists(\OCP\AppFramework\Http::class)) {
        eval('namespace OCP\AppFramework; class Http { public const STATUS_BAD_REQUEST = 400; public const STATUS_NOT_FOUND = 404; public const STATUS_INTERNAL_SERVER_ERROR = 500; }');
    }
    if (!class_exists(\OCP\AppFramework\Http\Response::class)) {
        eval('namespace OCP\AppFramework\Http; class Response {}');
    }
    if (!class_exists(\OCP\AppFramework\Http\DataResponse::class)) {
        eval('namespace OCP\AppFramework\Http; class DataResponse extends Response { public function __construct(mixed $data = [], int $status = 200) {} }');
    }
    if (!class_exists(\OCP\AppFramework\Http\TemplateResponse::class)) {
        eval('namespace OCP\AppFramework\Http; class TemplateResponse extends Response { public function __construct(string $appName, string $templateName) {} }');
    }
    if (!class_exists(\OCP\AppFramework\Http\Attribute\NoAdminRequired::class)) {
        eval('namespace OCP\AppFramework\Http\Attribute; #[\Attribute(\Attribute::TARGET_METHOD)] class NoAdminRequired {}');
    }
    if (!class_exists(\OCP\AppFramework\Http\Attribute\NoCSRFRequired::class)) {
        eval('namespace OCP\AppFramework\Http\Attribute; #[\Attribute(\Attribute::TARGET_METHOD)] class NoCSRFRequired {}');
    }
}

namespace OCA\BrTop\AppInfo {
    if (!class_exists(Application::class)) {
        final class Application {
            public const APP_ID = 'brtop';
        }
    }
}

namespace {
    require __DIR__ . '/../../lib/Controller/PageController.php';
    require __DIR__ . '/../../lib/Controller/ApiController.php';

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
        'updateSettings',
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

    $seedDemo = new \ReflectionMethod(ApiController::class, 'seedDemo');
    if ($seedDemo->getAttributes(NoAdminRequired::class) !== []) {
        throw new \RuntimeException('seedDemo must remain restricted to Nextcloud admins.');
    }
    if ($seedDemo->getAttributes(NoCSRFRequired::class) !== []) {
        throw new \RuntimeException('seedDemo should keep the default CSRF protection.');
    }

    echo 'BRTop controller attribute smoke tests passed' . PHP_EOL;
}
