<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use OCA\BrTop\Exception\DocumentGenerationException;
use OCA\BrTop\Model\Meeting;
use OCA\BrTop\Repository\DocumentRepository;
use OCA\BrTop\Repository\MeetingRepository;
use OCA\BrTop\Model\ProtocolBlock;
use OCA\BrTop\Store\ProtocolBlockStore;
use OCP\Lock\LockedException;

class DocumentGenerationService {
    public function __construct(
        private AgendaService $agendaService,
        private InvitationSnapshotService $invitationSnapshotService,
        private FileExportService $fileExportService,
        private DocumentContentService $documentContentService,
        private DocumentRepository $documentRepository,
        private MeetingRepository $meetingRepository,
        private ProtocolBlockStore $protocolBlockStore,
        private BrtopLogger $logger,
        private OdtTemplateRenderer $odtRenderer,
        private DocumentTemplateProvider $templates,
        private BrtopSettingsService $settings
    ) {
    }

    public function generateInvitation(string $uid, Meeting $meeting): array {
        $created = [];
        $warnings = [];
        $meetingId = $this->meetingId($meeting);

        try {
            $tops = $this->agendaService->itemsForMeeting($meetingId);
            $recipients = $this->invitationSnapshotService->getOrCreateForMeeting($meetingId, (string)$meeting->meetingDate, $uid);

            $basePath = $this->fileExportService->meetingFolder($meeting);
            $this->fileExportService->ensureFolder($uid, $basePath);

            $subject = $this->documentContentService->invitationSubject($meeting);
            $emailDocument = $this->documentContentService->renderInvitationEmail($meeting, $tops, $recipients);
            $email = $emailDocument['content'];
            $markdownDocument = $this->documentContentService->renderInvitationMarkdown($subject, $email);
            $markdown = $markdownDocument['content'];
            $recipientList = $this->documentContentService->invitationRecipientList($recipients);

            $this->writeDocumentSafely($uid, $meetingId, $basePath, '01_Einladung_Email.txt', $email, 'invitation_email', 'Einladung E-Mail', $created, $warnings, $emailDocument['templateKey'], $emailDocument['version']);
            $this->writeDocumentSafely($uid, $meetingId, $basePath, '01_Ladung.md', $markdown, 'invitation_markdown', 'Ladung', $created, $warnings, $markdownDocument['templateKey'], $markdownDocument['version']);
            $this->writeDocumentSafely($uid, $meetingId, $basePath, '01_Ladungsliste.txt', $recipientList, 'invitation_recipients', 'Ladungsliste', $created, $warnings);
            if ($warnings === []) {
                $this->meetingRepository->markInvitationCreated($meetingId);
            }

            return [
                'ok' => count($warnings) === 0,
                'type' => 'invitation',
                'folder' => $basePath,
                'subject' => $subject,
                'email' => $email,
                'created' => $created,
                'warnings' => $warnings,
                'recipientCount' => count($recipients),
            ];
        } catch (\Throwable $e) {
            throw new DocumentGenerationException(['created' => $created], $e);
        }
    }

    public function generateProtocol(string $uid, Meeting $meeting): array {
        $created = [];
        $warnings = [];
        $meetingId = $this->meetingId($meeting);
        $meetingData = $meeting->toRepositoryData();

        try {
            $tops = $this->withProtocolBlocks($meetingId, $this->agendaService->itemsForMeeting($meetingId));

            $basePath = $this->fileExportService->meetingFolder($meeting);
            $this->fileExportService->ensureFolder($uid, $basePath);

            $protocolDocument = $this->documentContentService->renderProtocol($meeting, $tops);
            $this->writeDocument($uid, $meetingId, $basePath, '02_Protokollvorlage.md', $protocolDocument['content'], 'protocol_markdown', 'Protokollvorlage', $protocolDocument['templateKey'], $protocolDocument['version']);
            $created[] = '02_Protokollvorlage.md';

            try {
                $odtTemplate = $this->templates->active('protocol_odt');
                $odt = $this->odtRenderer->renderProtocol(
                    $meetingData,
                    $tops,
                    $odtTemplate['content'],
                    $this->settings->documentOrganization()
                );
                $this->writeDocument($uid, $meetingId, $basePath, '02_Protokollvorlage.odt', $odt, 'protocol_odt', 'Protokollvorlage ODT', $odtTemplate['templateKey'], $odtTemplate['version']);
                $created[] = '02_Protokollvorlage.odt';
            } catch (\Throwable $e) {
                $this->logger->error('generate_protocol_odt', $e, [
                    'meeting_id' => $meetingId,
                    'document_type' => 'protocol',
                ]);
                $warnings[] = 'Das Protokoll konnte nicht als ODT erzeugt werden. Details stehen im Nextcloud-Log.';
            }

            return [
                'ok' => count($warnings) === 0,
                'type' => 'protocol',
                'folder' => $basePath,
                'created' => $created,
                'warnings' => $warnings,
            ];
        } catch (\Throwable $e) {
            if ($e instanceof DocumentGenerationException) {
                throw $e;
            }

            throw new DocumentGenerationException(['created' => $created], $e);
        }
    }

    public function generateResolutions(string $uid, Meeting $meeting): array {
        $created = [];
        $meetingId = $this->meetingId($meeting);

        try {
            $tops = $this->agendaService->itemsForMeeting($meetingId);

            $basePath = $this->fileExportService->meetingFolder($meeting);
            $this->fileExportService->ensureFolder($uid, $basePath);

            foreach ($tops as $top) {
                if (!$this->agendaService->isResolutionItem($top)) {
                    continue;
                }

                $resolutionCount = max(1, $this->agendaService->resolutionCount($top));
                for ($resolutionIndex = 1; $resolutionIndex <= $resolutionCount; $resolutionIndex++) {
                    $number = $this->agendaService->numberForItem($top);
                    $suffix = $resolutionCount > 1 ? '_' . str_pad((string)$resolutionIndex, 2, '0', STR_PAD_LEFT) : '';
                    $filename = '03_Beschluss_' . $number . $suffix . '_' . $this->fileExportService->safeName((string)$top['subject']) . '.md';
                    $resolutionDocument = $this->documentContentService->renderResolution($meeting, $top, $resolutionIndex);
                    $this->writeDocument(
                        $uid,
                        $meetingId,
                        $basePath,
                        $filename,
                        $resolutionDocument['content'],
                        'resolution_markdown',
                        'Beschluss ' . $number . ($resolutionCount > 1 ? '.' . $resolutionIndex : ''),
                        $resolutionDocument['templateKey'],
                        $resolutionDocument['version']
                    );
                    $created[] = $filename;
                }
            }

            return [
                'ok' => true,
                'type' => 'resolutions',
                'folder' => $basePath,
                'created' => $created,
                'message' => count($created) === 0 ? 'Keine TOPs mit Beschlussmarkierung vorhanden.' : '',
            ];
        } catch (\Throwable $e) {
            throw new DocumentGenerationException(['created' => $created], $e);
        }
    }

    private function writeDocument(
        string $uid,
        int $meetingId,
        string $basePath,
        string $filename,
        string $content,
        string $documentType,
        string $title,
        ?string $templateKey = null,
        ?int $templateVersion = null
    ): void {
        $path = $basePath . '/' . $filename;
        $this->fileExportService->putUserFile($uid, $path, $content);
        if ($documentType === 'resolution_markdown') {
            $this->documentRepository->insert($meetingId, $documentType, $title, $path, $templateKey, $templateVersion);
            return;
        }

        $this->documentRepository->replaceForMeetingAndType($meetingId, $documentType, $title, $path, $templateKey, $templateVersion);
    }

    private function writeDocumentSafely(
        string $uid,
        int $meetingId,
        string $basePath,
        string $filename,
        string $content,
        string $documentType,
        string $title,
        array &$created,
        array &$warnings,
        ?string $templateKey = null,
        ?int $templateVersion = null
    ): void {
        try {
            $this->writeDocument($uid, $meetingId, $basePath, $filename, $content, $documentType, $title, $templateKey, $templateVersion);
            $created[] = $filename;
        } catch (\Throwable $e) {
            if (!$this->isLockException($e)) {
                throw $e;
            }

            $warnings[] = $filename . ' konnte nicht überschrieben werden, weil die Datei gerade in Nextcloud geöffnet oder gesperrt ist.';
        }
    }

    private function isLockException(\Throwable $e): bool {
        do {
            if ($e instanceof LockedException) {
                return true;
            }
            $e = $e->getPrevious();
        } while ($e instanceof \Throwable);

        return false;
    }

    private function meetingId(Meeting $meeting): int {
        $meetingId = (int)$meeting->id;
        if ($meetingId <= 0) {
            throw new \InvalidArgumentException('Dokumenterzeugung benötigt ein gespeichertes Meeting.');
        }

        return $meetingId;
    }

    private function withProtocolBlocks(int $meetingId, array $tops): array {
        $blocksByTop = $this->protocolBlockStore->groupedForMeeting($meetingId);

        foreach ($tops as &$top) {
            $top['protocol_blocks'] = array_map(
                static fn(ProtocolBlock $block): array => $block->toArray(),
                $blocksByTop[(int)$top['id']] ?? []
            );
        }

        return $tops;
    }
}
