<?php

declare(strict_types=1);

use OCA\BrTop\Model\Meeting;
use OCA\BrTop\Repository\DocumentRepository;
use OCA\BrTop\Repository\MeetingRepository;
use OCA\BrTop\Service\AgendaService;
use OCA\BrTop\Service\BrtopLogger;
use OCA\BrTop\Service\BrtopSettingsService;
use OCA\BrTop\Service\DocumentContentService;
use OCA\BrTop\Service\DocumentGenerationService;
use OCA\BrTop\Service\DocumentTemplateProvider;
use OCA\BrTop\Service\FileExportService;
use OCA\BrTop\Service\InvitationSnapshotService;
use OCA\BrTop\Service\OdtTemplateRenderer;
use OCA\BrTop\Store\ProtocolBlockStore;
use function OCA\BrTop\Tests\assertSameValue;

$agenda = new class extends AgendaService {
    public function __construct() {}
    public function itemsForMeeting(int $meetingId): array {
        return [[
            'id' => 9, 'meeting_id' => $meetingId, 'subject' => 'Beschluss', 'type' => 'other',
            'requires_resolution' => 1, 'resolution_count' => 1, 'agenda_number' => '1',
        ]];
    }
    public function isResolutionItem(array|\OCA\BrTop\Model\AgendaItem $item): bool { return true; }
    public function resolutionCount(array|\OCA\BrTop\Model\AgendaItem $item): int { return 1; }
    public function numberForItem(array|\OCA\BrTop\Model\AgendaItem $item): string { return '1'; }
};
$snapshots = new class extends InvitationSnapshotService {
    public function __construct() {}
    public function getOrCreateForMeeting(int $meetingId, string $meetingDate = '', string $actorUid = ''): array { return []; }
};
$files = new class extends FileExportService {
    public array $writes = [];
    public function __construct() {}
    public function meetingFolder(array|Meeting $meeting): string { return 'BR-Sitzungen/Test'; }
    public function ensureFolder(string $uid, string $path): void {}
    public function putUserFile(string $uid, string $path, string $content): void { $this->writes[$path] = $content; }
    public function safeName(string $value): string { return 'Beschluss'; }
};
$content = new class extends DocumentContentService {
    public function __construct() {}
    public function invitationSubject(array|Meeting $meeting): string { return 'Einladung'; }
    public function renderInvitationEmail(array|Meeting $meeting, array $tops, array $recipients = []): array { return $this->doc('invitation_email', 3, 'E-Mail'); }
    public function renderInvitationMarkdown(string $subject, string $email): array { return $this->doc('invitation_markdown', 4, 'Markdown'); }
    public function invitationEmail(array|Meeting $meeting, array $tops, array $recipients = []): string { return 'E-Mail'; }
    public function invitationMarkdown(string $subject, string $email): string { return 'Markdown'; }
    public function invitationRecipientList(array $recipients): string { return 'Liste'; }
    public function renderProtocol(array|Meeting $meeting, array $tops): array { return $this->doc('protocol_markdown', 5, 'Protokoll'); }
    public function protocolTemplate(array|Meeting $meeting, array $tops): string { return 'Protokoll'; }
    public function renderResolution(array|Meeting $meeting, array|\OCA\BrTop\Model\AgendaItem $top, int $resolutionIndex = 1): array { return $this->doc('resolution_markdown', 6, 'Beschluss'); }
    public function resolutionDocument(array|Meeting $meeting, array|\OCA\BrTop\Model\AgendaItem $top, int $resolutionIndex = 1): string { return 'Beschluss'; }
    private function doc(string $key, int $version, string $content): array { return ['templateKey' => $key, 'version' => $version, 'content' => $content]; }
};
$documents = new class extends DocumentRepository {
    public array $writes = [];
    public function __construct() {}
    public function insert(int $meetingId, string $type, string $title, string $path, ?string $templateKey = null, ?int $templateVersion = null): void {
        $this->writes[] = [$type, $templateKey, $templateVersion];
    }
    public function replaceForMeetingAndType(int $meetingId, string $type, string $title, string $path, ?string $templateKey = null, ?int $templateVersion = null): void {
        $this->writes[] = [$type, $templateKey, $templateVersion];
    }
};
$meetings = new class extends MeetingRepository {
    public function __construct() {}
    public function markInvitationCreated(int $meetingId): void {}
};
$blocks = new class extends ProtocolBlockStore {
    public function __construct() {}
    public function groupedForMeeting(int $meetingId): array { return []; }
};
$logger = new class extends BrtopLogger {
    public function __construct() {}
    public function error(string $action, Throwable $exception, array $context = []): void {}
};
$odt = new class extends OdtTemplateRenderer {
    public function renderProtocol(array $meeting, array $tops, string $templateContent, array $organization): string { return 'ODT'; }
};
$templates = new class implements DocumentTemplateProvider {
    public function renderActive(string $templateKey, array $values): array { throw new LogicException('not used'); }
    public function active(string $templateKey): array { return ['templateKey' => 'protocol_odt', 'version' => 8, 'content' => 'ODT-TEMPLATE']; }
};
$settings = new class extends BrtopSettingsService {
    public function __construct() {}
    public function documentOrganization(): array { return ['councilName' => 'BR', 'councilAddress' => 'Adresse', 'issuePlace' => 'Ort']; }
};

$service = new DocumentGenerationService(
    $agenda,
    $snapshots,
    $files,
    $content,
    $documents,
    $meetings,
    $blocks,
    $logger,
    $odt,
    $templates,
    $settings
);
$meeting = new Meeting(['id' => 2, 'meeting_date' => '2026-10-27', 'title' => 'Test']);
$service->generateInvitation('test-user', $meeting);
$service->generateProtocol('test-user', $meeting);
$service->generateResolutions('test-user', $meeting);

$byType = [];
foreach ($documents->writes as $write) {
    $byType[$write[0]] = [$write[1], $write[2]];
}
assertSameValue(['invitation_email', 3], $byType['invitation_email'], 'Invitation e-mail metadata should store the exact template version.');
assertSameValue(['invitation_markdown', 4], $byType['invitation_markdown'], 'Invitation Markdown metadata should store the exact template version.');
assertSameValue([null, null], $byType['invitation_recipients'], 'The canonical recipient snapshot is not an editable template.');
assertSameValue(['protocol_markdown', 5], $byType['protocol_markdown'], 'Protocol Markdown metadata should store the exact template version.');
assertSameValue(['protocol_odt', 8], $byType['protocol_odt'], 'Protocol ODT metadata should store the exact template version.');
assertSameValue(['resolution_markdown', 6], $byType['resolution_markdown'], 'Resolution metadata should store the exact template version.');

echo 'DocumentGeneration template metadata tests passed' . PHP_EOL;
