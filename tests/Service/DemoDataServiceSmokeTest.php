<?php

declare(strict_types=1);


use OCA\BrTop\Service\AgendaMutationService;
use OCA\BrTop\Service\BrDemoUserProvisioningService;
use OCA\BrTop\Service\BrRosterService;
use OCA\BrTop\Service\DemoDataService;
use OCA\BrTop\Service\MeetingService;
use function OCA\BrTop\Tests\assertSameValue;

$meetingService = new class extends MeetingService {
    public array $created = [];

    public function __construct() {
    }

    public function create(
        string $uid,
        string $title,
        string $meetingDate,
        string $meetingTime,
        string $location,
        string $meetingType,
        string $committeeCode
    ): int {
        $this->created = compact(
            'uid',
            'title',
            'meetingDate',
            'meetingTime',
            'location',
            'meetingType',
            'committeeCode'
        );

        return 23;
    }
};

$agendaMutationService = new class extends AgendaMutationService {
    public array $items = [];

    public function __construct() {
    }

    public function addItem(
        int $meetingId,
        string $type,
        string $subject,
        string $personName,
        string $legalBasis,
        string $resolutionText,
        bool $requiresResolution,
        string $agendaItemKind,
        int $parentId,
        string $protocolContent,
        string $invitationNote,
        string $attachmentPaths,
        int $resolutionCount
    ): int {
        $this->items[] = compact(
            'meetingId',
            'type',
            'subject',
            'personName',
            'legalBasis',
            'resolutionText',
            'requiresResolution',
            'agendaItemKind',
            'parentId',
            'protocolContent',
            'invitationNote',
            'attachmentPaths',
            'resolutionCount'
        );

        return count($this->items);
    }
};

$demoUserProvisioningService = new class extends BrDemoUserProvisioningService {
    public function __construct() {
    }

    public function ensureDemoUsers(): array {
        return [
            'created' => ['brtop-lz-01'],
            'addedToGroup' => ['admin', 'brtop-lz-01'],
        ];
    }
};

$service = new DemoDataService($meetingService, $agendaMutationService, new BrRosterService(), $demoUserProvisioningService);
$result = $service->seedForOwner('simon');

assertSameValue(23, $result['meetingId'], 'Demo seeding should return the created meeting id.');
assertSameValue(['brtop-lz-01'], $result['demoUsers']['created'], 'Demo seeding should report provisioned users.');
assertSameValue(['admin', 'brtop-lz-01'], $result['demoUsers']['addedToGroup'], 'Demo seeding should report BR group assignments.');
assertSameValue(13, $result['demoCouncil']['councilSize'], 'Demo seeding should describe the 13-seat works council.');
assertSameValue('female', $result['demoCouncil']['minorityGender'], 'Demo seeding should mark female as minority gender.');
assertSameValue(29, count($result['demoCouncil']['members']), 'Demo seeding should expose regular members and replacements.');
assertSameValue('simon', $meetingService->created['uid'], 'Demo meeting should be created for the current user.');
assertSameValue('Ordentliche BR-Sitzung', $meetingService->created['title'], 'Demo meeting should keep the existing title.');
assertSameValue('10:00', $meetingService->created['meetingTime'], 'Demo meeting should keep the existing time.');
assertSameValue('custom', $meetingService->created['meetingType'], 'Demo meeting should keep the existing meeting type.');
assertSameValue(7, count($agendaMutationService->items), 'Demo seeding should create the existing seven agenda items.');
assertSameValue('protocol', $agendaMutationService->items[0]['type'], 'Demo seeding should start with the protocol TOP.');
assertSameValue('consultation_report', $agendaMutationService->items[6]['type'], 'Demo seeding should keep the consultation report TOP.');
assertSameValue(1, $agendaMutationService->items[1]['resolutionCount'], 'Resolution demo TOPs should create one resolution.');
assertSameValue(0, $agendaMutationService->items[6]['resolutionCount'], 'Non-resolution demo TOPs should not create resolutions.');

echo 'DemoDataService smoke tests passed' . PHP_EOL;
