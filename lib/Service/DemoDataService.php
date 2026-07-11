<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use DateTimeImmutable;

class DemoDataService {
    public function __construct(
        private MeetingService $meetingService,
        private AgendaMutationService $agendaMutationService,
        private BrRosterService $rosterService,
        private BrDemoUserProvisioningService $demoUserProvisioningService
    ) {
    }

    public function seedForOwner(string $uid): array {
        $demoUsers = $this->demoUserProvisioningService->ensureDemoUsers();

        $meetingId = $this->meetingService->create(
            $uid,
            'Ordentliche BR-Sitzung',
            (new DateTimeImmutable('+7 days'))->format('Y-m-d'),
            '10:00',
            'BR-Büro / Videokonferenz',
            'custom',
            ''
        );

        foreach ($this->demoAgendaItems() as $item) {
            $this->agendaMutationService->addItem(
                $meetingId,
                $item['type'],
                $item['subject'],
                $item['personName'],
                $item['legalBasis'],
                $item['resolutionText'],
                $item['requiresResolution'],
                $item['agendaItemKind'],
                0,
                '',
                '',
                '',
                $item['requiresResolution'] ? 1 : 0
            );
        }

        return [
            'meetingId' => $meetingId,
            'demoUsers' => $demoUsers,
            'demoCouncil' => $this->rosterService->demoCouncil(),
        ];
    }

    private function demoAgendaItems(): array {
        return [
            [
                'type' => 'protocol',
                'subject' => 'Protokoll der letzten Sitzung',
                'personName' => '',
                'legalBasis' => '',
                'resolutionText' => 'Wer stimmt dem Protokoll der letzten Sitzung zu?',
                'requiresResolution' => true,
                'agendaItemKind' => 'resolution',
            ],
            [
                'type' => 'personnel_99',
                'subject' => 'Einstellung Hans Müller',
                'personName' => 'Hans Müller',
                'legalBasis' => '§ 99 BetrVG',
                'resolutionText' => 'Wer verweigert die Zustimmung zur Einstellung von Hans Müller gemäß § 99 BetrVG?',
                'requiresResolution' => true,
                'agendaItemKind' => 'resolution',
            ],
            [
                'type' => 'personnel_99',
                'subject' => 'Eingruppierung Max Muster',
                'personName' => 'Max Muster',
                'legalBasis' => '§ 99 BetrVG',
                'resolutionText' => 'Wer verweigert die Zustimmung zur Eingruppierung von Max Muster gemäß § 99 BetrVG?',
                'requiresResolution' => true,
                'agendaItemKind' => 'resolution',
            ],
            [
                'type' => 'personnel_100',
                'subject' => 'Vorläufige Einstellung Mathilda Müßig',
                'personName' => 'Mathilda Müßig',
                'legalBasis' => '§ 100 BetrVG',
                'resolutionText' => 'Wer bestreitet, dass die vorläufige Durchführung der personellen Maßnahme aus sachlichen Gründen dringend erforderlich ist?',
                'requiresResolution' => true,
                'agendaItemKind' => 'resolution',
            ],
            [
                'type' => 'personnel_102',
                'subject' => 'Anhörung Kündigung Nina Narrativ',
                'personName' => 'Nina Narrativ',
                'legalBasis' => '§ 102 BetrVG',
                'resolutionText' => 'Wer widerspricht der beabsichtigten Kündigung von Nina Narrativ gemäß § 102 BetrVG?',
                'requiresResolution' => true,
                'agendaItemKind' => 'resolution',
            ],
            [
                'type' => 'organisation',
                'subject' => 'Planung nächste Sitzung',
                'personName' => '',
                'legalBasis' => '',
                'resolutionText' => '',
                'requiresResolution' => false,
                'agendaItemKind' => 'discussion',
            ],
            [
                'type' => 'consultation_report',
                'subject' => 'Bericht aus den Sprechstunden seit der letzten Sitzung',
                'personName' => '',
                'legalBasis' => '',
                'resolutionText' => '',
                'requiresResolution' => false,
                'agendaItemKind' => 'report',
            ],
        ];
    }
}
