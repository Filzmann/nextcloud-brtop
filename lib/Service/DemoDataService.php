<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use DateTimeImmutable;
use OCP\IL10N;

class DemoDataService {
    public function __construct(private MeetingService $meetingService,
        private AgendaMutationService $agendaMutationService,
        private BrRosterService $rosterService,
        private BrDemoUserProvisioningService $demoUserProvisioningService,
        private ?IL10N $l10n = null) {
    }

    /** Lokalisierung fachlicher Texte; im Test ohne IL10N identity. */
    private function t(string $text): string
    {
        return $this->l10n?->t($text) ?? $text;
    }

    public function seedForOwner(string $uid): array {
        $demoUsers = $this->demoUserProvisioningService->ensureDemoUsers();

        $meetingId = $this->meetingService->create(
            $uid,
            $this->t('Ordentliche BR-Sitzung'),
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
                'subject' => $this->t('Protokoll der letzten Sitzung'),
                'personName' => '',
                'legalBasis' => '',
                'resolutionText' => $this->t('Wer stimmt dem Protokoll der letzten Sitzung zu?'),
                'requiresResolution' => true,
                'agendaItemKind' => 'resolution',
            ],
            [
                'type' => 'personnel_99',
                'subject' => $this->t('Einstellung Hans Müller'),
                'personName' => $this->t('Hans Müller'),
                'legalBasis' => $this->t('§ 99 BetrVG'),
                'resolutionText' => $this->t('Wer verweigert die Zustimmung zur Einstellung von Hans Müller gemäß § 99 BetrVG?'),
                'requiresResolution' => true,
                'agendaItemKind' => 'resolution',
            ],
            [
                'type' => 'personnel_99',
                'subject' => $this->t('Eingruppierung Max Muster'),
                'personName' => $this->t('Max Muster'),
                'legalBasis' => $this->t('§ 99 BetrVG'),
                'resolutionText' => $this->t('Wer verweigert die Zustimmung zur Eingruppierung von Max Muster gemäß § 99 BetrVG?'),
                'requiresResolution' => true,
                'agendaItemKind' => 'resolution',
            ],
            [
                'type' => 'personnel_100',
                'subject' => $this->t('Vorläufige Einstellung Mathilda Müßig'),
                'personName' => $this->t('Mathilda Müßig'),
                'legalBasis' => $this->t('§ 100 BetrVG'),
                'resolutionText' => $this->t('Wer bestreitet, dass die vorläufige Durchführung der personellen Maßnahme aus sachlichen Gründen dringend erforderlich ist?'),
                'requiresResolution' => true,
                'agendaItemKind' => 'resolution',
            ],
            [
                'type' => 'personnel_102',
                'subject' => $this->t('Anhörung Kündigung Nina Narrativ'),
                'personName' => $this->t('Nina Narrativ'),
                'legalBasis' => $this->t('§ 102 BetrVG'),
                'resolutionText' => $this->t('Wer widerspricht der beabsichtigten Kündigung von Nina Narrativ gemäß § 102 BetrVG?'),
                'requiresResolution' => true,
                'agendaItemKind' => 'resolution',
            ],
            [
                'type' => 'organisation',
                'subject' => $this->t('Planung nächste Sitzung'),
                'personName' => '',
                'legalBasis' => '',
                'resolutionText' => '',
                'requiresResolution' => false,
                'agendaItemKind' => 'discussion',
            ],
            [
                'type' => 'consultation_report',
                'subject' => $this->t('Bericht aus den Sprechstunden seit der letzten Sitzung'),
                'personName' => '',
                'legalBasis' => '',
                'resolutionText' => '',
                'requiresResolution' => false,
                'agendaItemKind' => 'report',
            ],
        ];
    }
}
