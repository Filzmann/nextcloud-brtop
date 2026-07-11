<?php

declare(strict_types=1);

namespace OCA\BrTop\Controller;

use OCA\BrTop\AppInfo\Application;
use OCA\BrTop\Exception\DocumentGenerationException;
use OCA\BrTop\Model\Meeting;
use OCA\BrTop\Service\AgendaMutationService;
use OCA\BrTop\Service\AgendaTemplateService;
use OCA\BrTop\Service\BrAccessService;
use OCA\BrTop\Service\BrtopLogger;
use OCA\BrTop\Service\BrtopSettingsService;
use OCA\BrTop\Service\DemoDataService;
use OCA\BrTop\Service\DocumentGenerationService;
use OCA\BrTop\Service\MeetingService;
use OCA\BrTop\Service\MeetingAbsenceService;
use OCA\BrTop\Service\MeetingStateService;
use OCA\BrTop\Service\LegislatureService;
use OCA\BrTop\Service\ProtocolBlockService;
use OCA\LocalBase\Controller\ApiResponder;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IUserSession;

class ApiController extends Controller {
    public function __construct(
        IRequest $request,
        private IUserSession $userSession,
        private BrtopLogger $logger,
        private BrtopSettingsService $settings,
        private AgendaTemplateService $agendaTemplateService,
        private AgendaMutationService $agendaMutationService,
        private MeetingStateService $meetingStateService,
        private DocumentGenerationService $documentGenerationService,
        private MeetingService $meetingService,
        private DemoDataService $demoDataService,
        private ProtocolBlockService $protocolBlockService,
        private LegislatureService $legislatureService,
        private MeetingAbsenceService $meetingAbsenceService,
        private BrAccessService $accessService,
        private ApiResponder $responder
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    private function uid(): string {
        $user = $this->userSession->getUser();
        if ($user === null) {
            throw new \RuntimeException('Nicht angemeldet');
        }
        return $user->getUID();
    }

    #[NoAdminRequired]
    public function state(): DataResponse {
        $uid = $this->uid();
        return new DataResponse([
            'meetings' => $this->meetingStateService->meetingsForOwner($uid),
            'settings' => $this->settingsPayload(),
            'capabilities' => [
                'canManageLegislature' => $this->accessService->isAdmin($uid),
            ],
            'notice' => 'Standardstruktur: 1 Protokolle, 2 Personelle Angelegenheiten (§99/§100/§102), 3 Arbeitsorganisatorisches, 4 Sprechstundenbericht. Einladung zusammengefasst, Protokoll und Beschlüsse getrennt.'
        ]);
    }

    public function legislature(): DataResponse {
        return new DataResponse([
            'ok' => true,
            'legislature' => $this->legislatureService->latest(),
        ]);
    }

    public function saveLegislature(string $configurationJson): DataResponse {
        return $this->responder->respond(
            function () use ($configurationJson): array {
                $payload = json_decode($configurationJson, true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($payload)) {
                    throw new \InvalidArgumentException('Die Legislaturdaten sind ungueltig.');
                }
                return [
                    'ok' => true,
                    'legislature' => $this->legislatureService->saveDraft($payload, $this->uid()),
                ];
            },
            [$this->logger, 'error'],
            'save_legislature',
            [],
            'Die Legislatur konnte nicht gespeichert werden.'
        );
    }

    public function activateLegislature(int $legislatureId): DataResponse {
        return $this->responder->respond(
            fn(): array => [
                'ok' => true,
                'legislature' => $this->legislatureService->activate($legislatureId),
            ],
            [$this->logger, 'error'],
            'activate_legislature',
            ['legislature_id' => $legislatureId],
            'Die Legislatur konnte nicht aktiviert werden.'
        );
    }

    public function absenceSuggestions(int $meetingId): DataResponse {
        return new DataResponse([
            'ok' => true,
            'absenceState' => $this->meetingAbsenceService->prepare($meetingId),
        ]);
    }

    public function saveConfirmedAbsences(int $meetingId, string $memberIdsJson = '[]'): DataResponse {
        return $this->responder->respond(
            function () use ($meetingId, $memberIdsJson): array {
                $memberIds = json_decode($memberIdsJson, true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($memberIds)) {
                    throw new \InvalidArgumentException('Die Verhinderungsliste ist ungueltig.');
                }
                return [
                    'ok' => true,
                    'absenceState' => $this->meetingAbsenceService->saveConfirmed($meetingId, $memberIds, $this->uid()),
                ];
            },
            [$this->logger, 'error'],
            'save_confirmed_absences',
            ['meeting_id' => $meetingId],
            'Die bestaetigten Verhinderungen konnten nicht gespeichert werden.'
        );
    }

    private function settingsPayload(): array {
        $settings = $this->settings->values();
        $settings['regularAgendaTemplateJson'] = $this->agendaTemplateService->jsonForSettings();

        return $settings;
    }

    public function updateSettings(
        string $defaultMeetingTitle,
        int $regularMeetingWeekday,
        int $invitationWeekday,
        string $defaultMeetingTime,
        string $defaultLocation,
        string $memberGroupName,
        string $regularAgendaTemplateJson = ''
    ): DataResponse {
        return $this->responder->respond(
            function () use (
                $defaultMeetingTitle,
                $regularMeetingWeekday,
                $invitationWeekday,
                $defaultMeetingTime,
                $defaultLocation,
                $memberGroupName,
                $regularAgendaTemplateJson
            ): array {
                $regularAgendaTemplateJson = $this->agendaTemplateService->normalizeJsonForStorage($regularAgendaTemplateJson);

                $this->settings->save(
                    $defaultMeetingTitle,
                    $regularMeetingWeekday,
                    $invitationWeekday,
                    $defaultMeetingTime,
                    $defaultLocation,
                    $memberGroupName,
                    $regularAgendaTemplateJson
                );

                return [
                    'ok' => true,
                    'settings' => $this->settingsPayload(),
                ];
            },
            [$this->logger, 'error'],
            'update_settings',
            [],
            'Die Einstellungen konnten nicht gespeichert werden. Details stehen im Nextcloud-Log.'
        );
    }

    #[NoAdminRequired]
    public function createMeeting(
        string $title,
        string $meetingDate,
        string $meetingTime = '',
        string $location = '',
        string $meetingType = 'custom',
        string $committeeCode = ''
    ): DataResponse {
        return new DataResponse([
            'ok' => true,
            'id' => $this->meetingService->create(
                $this->uid(),
                $title,
                $meetingDate,
                $meetingTime,
                $location,
                $meetingType,
                $committeeCode
            ),
        ]);
    }

    #[NoAdminRequired]
    public function planNextRegularMeeting(): DataResponse {
        return $this->responder->respond(
            function (): array {
                $planned = $this->meetingService->planNextRegular($this->uid());

                return [
                    'ok' => true,
                ] + $planned;
            },
            [$this->logger, 'error'],
            'plan_next_regular_meeting',
            [],
            'Die nächste BR-Sitzung konnte nicht geplant werden. Details stehen im Nextcloud-Log.'
        );
    }

    #[NoAdminRequired]
    public function addTop(
        int $meetingId,
        string $type,
        string $subject,
        string $personName = '',
        string $legalBasis = '',
        string $resolutionText = '',
        bool $requiresResolution = false,
        string $agendaItemKind = '',
        int $parentId = 0,
        string $protocolContent = '',
        string $invitationNote = '',
        string $attachmentPaths = '',
        int $resolutionCount = 0
    ): DataResponse {
        return $this->agendaMutationResponse($meetingId, function () use (
            $meetingId,
            $type,
            $subject,
            $personName,
            $legalBasis,
            $resolutionText,
            $requiresResolution,
            $agendaItemKind,
            $parentId,
            $protocolContent,
            $invitationNote,
            $attachmentPaths,
            $resolutionCount
        ): void {
            $this->agendaMutationService->addItem(
                $meetingId,
                $type,
                $subject,
                $personName,
                $legalBasis,
                $resolutionText,
                $requiresResolution,
                $agendaItemKind,
                $parentId,
                $protocolContent,
                $invitationNote,
                $attachmentPaths,
                $resolutionCount
            );
        });
    }

    #[NoAdminRequired]
    public function moveTop(int $meetingId, int $topId, string $direction): DataResponse {
        return $this->agendaMutationResponse($meetingId, function () use ($meetingId, $topId, $direction): void {
            $this->agendaMutationService->moveItem($meetingId, $topId, $direction);
        });
    }

    #[NoAdminRequired]
    public function changeTopDepth(int $meetingId, int $topId, string $direction): DataResponse {
        return $this->agendaMutationResponse($meetingId, function () use ($meetingId, $topId, $direction): void {
            $this->agendaMutationService->changeItemDepth($meetingId, $topId, $direction);
        });
    }

    #[NoAdminRequired]
    public function updateTopSubject(int $meetingId, int $topId, string $subject): DataResponse {
        return $this->agendaMutationResponse($meetingId, function () use ($meetingId, $topId, $subject): void {
            $this->agendaMutationService->updateItemSubject($meetingId, $topId, $subject);
        });
    }

    #[NoAdminRequired]
    public function deleteTop(int $meetingId, int $topId): DataResponse {
        return $this->agendaMutationResponse(
            $meetingId,
            function () use ($meetingId, $topId): void {
                $this->agendaMutationService->deleteItemWithProtocolBlocks($meetingId, $topId);
            },
            'delete_top',
            'Der TOP konnte nicht gelöscht werden. Details stehen im Nextcloud-Log.',
            [
                'meeting_id' => $meetingId,
                'top_id' => $topId,
            ]
        );
    }

    #[NoAdminRequired]
    public function deleteMeeting(int $meetingId): DataResponse {
        return $this->responder->respond(
            function () use ($meetingId): array {
                $this->assertMeetingOwner($meetingId);
                $this->meetingService->delete($meetingId);

                return ['ok' => true];
            },
            [$this->logger, 'error'],
            'delete_meeting',
            ['meeting_id' => $meetingId],
            'Die Sitzung konnte nicht gelöscht werden. Details stehen im Nextcloud-Log.'
        );
    }

    #[NoAdminRequired]
    public function addProtocolBlock(
        int $meetingId,
        int $topId,
        string $blockType = 'text',
        string $content = ''
    ): DataResponse {
        $this->assertMeetingOwner($meetingId);

        try {
            $block = $this->protocolBlockService->addBlock($meetingId, $topId, $blockType, $content);
        } catch (\OutOfBoundsException $e) {
            return new DataResponse([
                'ok' => false,
                'message' => $e->getMessage(),
            ], Http::STATUS_NOT_FOUND);
        }

        return new DataResponse([
            'ok' => true,
            'block' => $block->toArray(),
        ]);
    }

    #[NoAdminRequired]
    public function updateProtocolBlock(
        int $meetingId,
        int $topId,
        int $blockId,
        string $content = ''
    ): DataResponse {
        $this->assertMeetingOwner($meetingId);

        try {
            $this->protocolBlockService->updateBlockContent($meetingId, $topId, $blockId, $content);
        } catch (\OutOfBoundsException $e) {
            return new DataResponse([
                'ok' => false,
                'message' => $e->getMessage(),
            ], Http::STATUS_NOT_FOUND);
        }

        return new DataResponse(['ok' => true]);
    }

    public function seedDemo(): DataResponse {
        return new DataResponse(['ok' => true] + $this->demoDataService->seedForOwner($this->uid()));
    }

    #[NoAdminRequired]
    public function generateInvitation(int $meetingId): DataResponse {
        $meeting = $this->assertMeetingOwner($meetingId);

        try {
            return new DataResponse($this->documentGenerationService->generateInvitation($this->uid(), $meeting));
        } catch (\Throwable $e) {
            return $this->documentErrorResponse(
                'generate_invitation',
                $e,
                'Die Einladung konnte nicht erzeugt werden. Details stehen im Nextcloud-Log.',
                ['meeting_id' => $meetingId, 'document_type' => 'invitation'],
                $this->partialDocumentData($e)
            );
        }
    }

    #[NoAdminRequired]
    public function generateProtocol(int $meetingId): DataResponse {
        $meeting = $this->assertMeetingOwner($meetingId);

        try {
            return new DataResponse($this->documentGenerationService->generateProtocol($this->uid(), $meeting));
        } catch (\Throwable $e) {
            return $this->documentErrorResponse(
                'generate_protocol',
                $e,
                'Das Protokoll konnte nicht erzeugt werden. Details stehen im Nextcloud-Log.',
                ['meeting_id' => $meetingId, 'document_type' => 'protocol'],
                $this->partialDocumentData($e)
            );
        }
    }

    #[NoAdminRequired]
    public function generateResolutions(int $meetingId): DataResponse {
        $meeting = $this->assertMeetingOwner($meetingId);

        try {
            return new DataResponse($this->documentGenerationService->generateResolutions($this->uid(), $meeting));
        } catch (\Throwable $e) {
            return $this->documentErrorResponse(
                'generate_resolutions',
                $e,
                'Die Beschlussdokumente konnten nicht erzeugt werden. Details stehen im Nextcloud-Log.',
                ['meeting_id' => $meetingId, 'document_type' => 'resolutions'],
                $this->partialDocumentData($e)
            );
        }
    }

    private function partialDocumentData(\Throwable $e): array {
        if ($e instanceof DocumentGenerationException) {
            return $e->responseData();
        }

        return [];
    }

    private function agendaMutationResponse(
        int $meetingId,
        callable $mutation,
        ?string $serverErrorAction = null,
        string $serverErrorMessage = '',
        array $serverErrorContext = []
    ): DataResponse {
        $this->assertMeetingOwner($meetingId);

        try {
            $mutation();
        } catch (\InvalidArgumentException $e) {
            return new DataResponse([
                'ok' => false,
                'message' => $e->getMessage(),
            ], Http::STATUS_BAD_REQUEST);
        } catch (\Throwable $e) {
            if ($serverErrorAction === null) {
                throw $e;
            }

            $this->logger->error($serverErrorAction, $e, $serverErrorContext);

            return new DataResponse([
                'ok' => false,
                'message' => $serverErrorMessage,
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }

        return new DataResponse(['ok' => true]);
    }

    private function documentErrorResponse(
        string $action,
        \Throwable $exception,
        string $message,
        array $context = [],
        array $data = []
    ): DataResponse {
        $this->logger->error($action, $exception, $context);

        return new DataResponse(array_merge($data, [
            'ok' => false,
            'message' => $message,
        ]), Http::STATUS_INTERNAL_SERVER_ERROR);
    }

    private function assertMeetingOwner(int $meetingId): Meeting {
        return $this->meetingService->assertOwnedMeeting($meetingId, $this->uid());
    }

}
