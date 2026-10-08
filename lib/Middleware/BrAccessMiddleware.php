<?php

declare(strict_types=1);

namespace OCA\BrTop\Middleware;

use OCA\BrTop\Controller\ApiController;
use OCA\BrTop\Controller\PageController;
use OCA\BrTop\Exception\AccessDeniedException;
use OCA\BrTop\Service\BrAccessService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Middleware;
use OCP\IUserSession;

class BrAccessMiddleware extends Middleware {
    private const ADMIN_ACTIONS = [
        'updateSettings',
        'seedDemo',
        'legislature',
        'saveLegislature',
        'activateLegislature',
        'absenceSuggestions',
        'saveConfirmedAbsences',
    ];

    public function __construct(
        private IUserSession $userSession,
        private BrAccessService $accessService
    ) {
    }

    public function beforeController(Controller $controller, string $methodName): void {
        if ($controller instanceof PageController) {
            return;
        }
        if (!$controller instanceof ApiController) {
            return;
        }

        $user = $this->userSession->getUser();
        $uid = $user?->getUID() ?? '';
        if ($controller instanceof ApiController && in_array($methodName, self::ADMIN_ACTIONS, true)) {
            $this->accessService->assertAdmin($uid);
            return;
        }

        $this->accessService->assertCanUse($uid);
    }

    public function afterException(Controller $controller, string $methodName, \Exception $exception) {
        if (!$exception instanceof AccessDeniedException) {
            throw $exception;
        }

        if ($controller instanceof ApiController) {
            return new DataResponse([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], Http::STATUS_FORBIDDEN);
        }

        $response = new TemplateResponse('core', '403', [], 'guest');
        $response->setStatus(Http::STATUS_FORBIDDEN);
        return $response;
    }
}
