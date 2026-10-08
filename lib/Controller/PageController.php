<?php

declare(strict_types=1);

namespace OCA\BrTop\Controller;

use OCA\BrTop\AppInfo\Application;
use OCA\BrTop\Service\BrAccessService;
use OCA\BrTop\Service\TemporaryAdminAccessService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IUserSession;

class PageController extends Controller {
    public function __construct(
        IRequest $request,
        private IUserSession $session,
        private BrAccessService $access,
        private TemporaryAdminAccessService $temporaryAdminAccess,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function index(): TemplateResponse {
        $uid = $this->session->getUser()?->getUID() ?? '';
        $canManageAdminAccess = $this->temporaryAdminAccess->canManage();
        $showMissingAdminGrant = $this->temporaryAdminAccess->currentAdminNeedsGrant();
        $hasBrTopAccess = $this->access->canUse($uid);
        if (!$hasBrTopAccess && !$canManageAdminAccess && !$showMissingAdminGrant) {
            return new TemplateResponse('core', '403', [], 'guest', Http::STATUS_FORBIDDEN);
        }
        return new TemplateResponse(Application::APP_ID, 'index', [
            'canManageAdminAccess' => $canManageAdminAccess,
            'showMissingAdminGrant' => $showMissingAdminGrant,
            'showAdminAccessLink' => $canManageAdminAccess && $showMissingAdminGrant,
            'hasBrTopAccess' => $hasBrTopAccess,
        ]);
    }
}
