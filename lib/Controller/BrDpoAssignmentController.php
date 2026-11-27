<?php

declare(strict_types=1);

namespace OCA\BrTop\Controller;

use JsonException;
use OCA\BrTop\AppInfo\Application;
use OCA\BrTop\Exception\AccessDeniedException;
use OCA\BrTop\Service\BrDpoAssignmentService;
use OCA\BrTop\Service\BrtopLogger;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Throwable;

final class BrDpoAssignmentController extends Controller {
    public function __construct(IRequest $request,
        private IUserSession $session,
        private BrDpoAssignmentService $assignments,
        private BrtopLogger $logger,
        private ?IL10N $l10n = null) {
        parent::__construct(Application::APP_ID, $request);
    }

    /** Lokalisierung fachlicher Texte; im Test ohne IL10N identity. */
    private function t(string $text): string
    {
        return $this->l10n?->t($text) ?? $text;
    }

    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function state(): JSONResponse {
        try {
            return new JSONResponse($this->assignments->state($this->uid()));
        } catch (AccessDeniedException) {
            return $this->denied();
        } catch (Throwable $error) {
            return $this->failed($error);
        }
    }

    #[NoAdminRequired]
    public function save(string $memberUidsJson): JSONResponse {
        try {
            $memberUids = json_decode($memberUidsJson, true, 16, JSON_THROW_ON_ERROR);
            if (!is_array($memberUids) || !array_is_list($memberUids)) {
                throw new \InvalidArgumentException('Ungültige Auswahl.');
            }
            return new JSONResponse(['assignedUids' => $this->assignments->replace($this->uid(), $memberUids)]);
        } catch (JsonException|\InvalidArgumentException $error) {
            return new JSONResponse(['message' => $this->t($error->getMessage())], Http::STATUS_BAD_REQUEST);
        } catch (AccessDeniedException) {
            return $this->denied();
        } catch (Throwable $error) {
            return $this->failed($error);
        }
    }

    private function uid(): string {
        return $this->session->getUser()?->getUID() ?? '';
    }

    private function denied(): JSONResponse {
        return new JSONResponse(['message' => $this->t('Keine Berechtigung.')], Http::STATUS_FORBIDDEN);
    }

    private function failed(Throwable $error): JSONResponse {
        $this->logger->error('br_dpo_assignment', $error);
        return new JSONResponse(['message' => $this->t('Die BR-Datenschutzberechtigung konnte nicht verarbeitet werden.')], Http::STATUS_INTERNAL_SERVER_ERROR);
    }
}
