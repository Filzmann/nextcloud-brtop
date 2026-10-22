<?php

declare(strict_types=1);

use OCA\BrTop\Model\AgendaItem;
use OCA\BrTop\Model\Meeting;
use OCA\BrTop\Service\AgendaAttachmentService;
use OCA\BrTop\Service\AgendaService;
use OCA\BrTop\Service\BrtopSettingsService;
use OCA\BrTop\Service\DocumentContentService;
use OCA\BrTop\Service\DocumentDateFormatter;
use OCA\BrTop\Service\DocumentTemplateProvider;
use OCA\BrTop\Service\InvitationContentService;
use function OCA\BrTop\Tests\assertContainsString;
use function OCA\BrTop\Tests\assertSameValue;

$templates = new class implements DocumentTemplateProvider {
    public function renderActive(string $templateKey, array $values): array {
        $bodyKey = match ($templateKey) {
            'invitation_email' => 'AGENDA',
            'invitation_markdown' => 'EMAIL_BODY',
            'protocol_markdown' => 'PROTOCOL_BODY',
            'resolution_markdown' => 'RESOLUTION_BODY',
            default => throw new InvalidArgumentException('Unexpected test template'),
        };
        return [
            'templateKey' => $templateKey,
            'version' => 7,
            'format' => str_ends_with($templateKey, 'markdown') ? 'markdown' : 'text',
            'status' => 'approved',
            'content' => "Kopf {$templateKey}\n" . ($values[$bodyKey] ?? ''),
        ];
    }

    public function active(string $templateKey): array {
        return ['templateKey' => $templateKey, 'version' => 7, 'format' => 'odt', 'status' => 'approved', 'content' => 'ODT'];
    }
};

$settings = new class extends BrtopSettingsService {
    public function __construct() {}
    public function documentOrganization(): array {
        return [
            'councilName' => 'Betriebsrat Beispiel',
            'councilAddress' => 'Beispielweg 1',
            'issuePlace' => 'Beispielstadt',
            'committeeCodes' => [],
            'invitationSalutation' => 'Guten Tag,',
            'invitationIntroduction' => 'hiermit laden wir ein.',
            'invitationClosing' => 'Viele Grüße',
            'invitationSender' => 'Der Betriebsrat',
        ];
    }
};

$agendaService = (new ReflectionClass(AgendaService::class))->newInstanceWithoutConstructor();
$dateFormatter = new DocumentDateFormatter();
$invitation = new InvitationContentService($agendaService, new AgendaAttachmentService(), $dateFormatter);
$content = new DocumentContentService($agendaService, $invitation, $dateFormatter, $templates, $settings);
$meeting = new Meeting([
    'id' => 12,
    'title' => 'Sitzung',
    'meeting_date' => '2026-10-27',
    'meeting_time' => '10:00',
    'location' => 'Sitzungsraum',
]);
$top = new AgendaItem([
    'id' => 4,
    'meeting_id' => 12,
    'position' => 1,
    'level' => 1,
    'type' => 'other',
    'subject' => 'Bericht',
    'agenda_number' => '1',
]);

$email = $content->renderInvitationEmail($meeting, [$top], []);
assertSameValue('invitation_email', $email['templateKey'], 'Invitation output should carry its template key.');
assertSameValue(7, $email['version'], 'Invitation output should carry its immutable template version.');
assertContainsString('1. Bericht', $email['content'], 'Canonical agenda content must survive the editable template boundary.');

$protocol = $content->renderProtocol($meeting, [$top]);
assertSameValue(7, $protocol['version'], 'Protocol output should carry the approved version.');
assertContainsString('Anwesenheit und ordnungsgemäße Einladung', $protocol['content'], 'Fixed protocol semantics must be rendered inside the required body placeholder.');

$resolutionTop = new AgendaItem([
    'id' => 5,
    'meeting_id' => 12,
    'position' => 2,
    'level' => 1,
    'type' => 'other',
    'subject' => 'Beschluss',
    'requires_resolution' => 1,
    'resolution_count' => 1,
    'resolution_text' => 'Wird zugestimmt?',
    'agenda_number' => '2',
]);
$resolution = $content->renderResolution($meeting, $resolutionTop, 1);
assertSameValue(7, $resolution['version'], 'Resolution output should carry the approved version.');
assertContainsString('Wird zugestimmt?', $resolution['content'], 'Canonical resolution semantics must survive the editable template boundary.');

echo 'DocumentContent template integration tests passed' . PHP_EOL;
