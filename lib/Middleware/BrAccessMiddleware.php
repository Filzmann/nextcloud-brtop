<?php

declare(strict_types=1);

namespace OCA\BrTop\Middleware;

use OCA\BrTop\Controller\ApiController;
use OCA\BrTop\Controller\PageController;
use OCA\BrTop\Exception\AccessDeniedException;
use OCA\BrTop\Service\BrAccessService;
use OCA\BrTop\Service\BrPermissionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Middleware;
use OCP\IUserSession;

class BrAccessMiddleware extends Middleware {
    private const ACTION_PERMISSIONS = [
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
        $permission = self::ACTION_PERMISSIONS[$methodName] ?? '';
        $this->accessService->assertPermission($uid, $permission);
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
