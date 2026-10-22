<?php

declare(strict_types=1);

namespace OCA\BrTop\Controller;

use OCA\BrTop\AppInfo\Application;
use OCA\BrTop\Exception\AccessDeniedException;
use OCA\BrTop\Service\BrAccessService;
use OCA\BrTop\Service\BrtopLogger;
use OCA\BrTop\Service\BrtopSettingsService;
use OCA\BrTop\Service\DocumentTemplateService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use Throwable;

/** Native admin route plus the stricter app-local temporary full-access gate. */
final class DocumentConfigurationController extends Controller {
    public function __construct(
        IRequest $request,
        private IUserSession $userSession,
        private BrAccessService $access,
        private BrtopSettingsService $settings,
        private DocumentTemplateService $templates,
        private BrtopLogger $logger
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoCSRFRequired]
    public function state(): JSONResponse {
        return $this->respond(fn(): array => [
            'ok' => true,
            'organization' => $this->settings->documentOrganization(),
            'templates' => $this->templates->metadata(),
        ], 'document_configuration_state');
    }

    #[NoCSRFRequired]
    public function template(string $templateKey, int $version): JSONResponse {
        return $this->respond(fn(): array => [
            'ok' => true,
            'template' => $this->templates->templateContent($templateKey, $version),
        ], 'document_configuration_template', ['template_key' => $templateKey, 'template_version' => $version]);
    }

    public function saveOrganization(
        string $councilName,
        string $councilAddress,
        string $issuePlace,
        string $committeeCodesJson,
        string $invitationSalutation,
        string $invitationIntroduction,
        string $invitationClosing,
        string $invitationSender
    ): JSONResponse {
        return $this->respond(fn(): array => [
            'ok' => true,
            'organization' => $this->settings->saveDocumentOrganization(
                $councilName,
                $councilAddress,
                $issuePlace,
                $committeeCodesJson,
                $invitationSalutation,
                $invitationIntroduction,
                $invitationClosing,
                $invitationSender
            ),
        ], 'save_document_organization');
    }

    public function saveDraft(string $templateKey, string $content, string $encoding = 'plain'): JSONResponse {
        return $this->respond(fn(): array => [
            'ok' => true,
            'template' => $this->encodeTemplateForJson(
                $this->templates->saveDraft($templateKey, $this->decodeContent($templateKey, $content, $encoding))
            ),
        ], 'save_document_template_draft', ['template_key' => $templateKey]);
    }

    public function preview(string $templateKey, string $content, string $encoding = 'plain'): JSONResponse {
        return $this->respond(function () use ($templateKey, $content, $encoding): array {
            $preview = $this->templates->preview($templateKey, $this->decodeContent($templateKey, $content, $encoding));
            if (($preview['format'] ?? '') === 'odt') {
                $preview['content'] = base64_encode((string)$preview['content']);
                $preview['encoding'] = 'base64';
            }
            return ['ok' => true, 'preview' => $preview];
        }, 'preview_document_template', ['template_key' => $templateKey]);
    }

    public function approve(string $templateKey, int $version): JSONResponse {
        return $this->respond(fn(): array => [
            'ok' => true,
            'template' => $this->encodeTemplateForJson($this->templates->approve($templateKey, $version)),
        ], 'approve_document_template', ['template_key' => $templateKey, 'template_version' => $version]);
    }

    private function encodeTemplateForJson(array $template): array {
        if (($template['format'] ?? '') === 'odt') {
            $template['content'] = base64_encode((string)($template['content'] ?? ''));
            $template['encoding'] = 'base64';
        }
        return $template;
    }

    private function decodeContent(string $templateKey, string $content, string $encoding): string {
        if ($templateKey === 'protocol_odt') {
            if ($encoding !== 'base64') {
                throw new \InvalidArgumentException('ODT-Vorlagen müssen Base64-kodiert übertragen werden.');
            }
            $decoded = base64_decode($content, true);
            if (!is_string($decoded)) {
                throw new \InvalidArgumentException('Die ODT-Vorlage ist nicht gültig Base64-kodiert.');
            }
            return $decoded;
        }
        if ($encoding !== 'plain') {
            throw new \InvalidArgumentException('Textvorlagen müssen als Klartext übertragen werden.');
        }
        return $content;
    }

    private function respond(callable $callback, string $action, array $context = []): JSONResponse {
        try {
            $user = $this->userSession->getUser();
            $this->access->assertAdmin($user?->getUID() ?? '');
            return new JSONResponse($callback());
        } catch (AccessDeniedException $e) {
            return new JSONResponse(['ok' => false, 'message' => $e->getMessage()], Http::STATUS_FORBIDDEN);
        } catch (\InvalidArgumentException $e) {
            return new JSONResponse(['ok' => false, 'message' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        } catch (Throwable $e) {
            $this->logger->error($action, $e, $context);
            return new JSONResponse([
                'ok' => false,
                'message' => 'Die Dokumentkonfiguration konnte nicht verarbeitet werden. Details stehen im Nextcloud-Log.',
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}
