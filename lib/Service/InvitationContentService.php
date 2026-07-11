<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use OCA\BrTop\Model\AgendaItem;
use OCA\BrTop\Model\Meeting;
use OCA\BrTop\Model\ProtocolBlock;

class InvitationContentService {
    public function __construct(
        private AgendaService $agendaService,
        private AgendaAttachmentService $agendaAttachmentService,
        private DocumentDateFormatter $dateFormatter
    ) {
    }

    public function subject(array|Meeting $meeting): string {
        $meetingData = $this->meetingData($meeting);

        return 'Ladung zur Sitzung am ' . $this->dateFormatter->germanDate((string)$meetingData['meeting_date']);
    }

    public function email(array|Meeting $meeting, array $tops, array $recipients = []): string {
        $meetingData = $this->meetingData($meeting);
        $lines = [];

        $lines[] = 'Liebe Kolleg*innen,';
        $lines[] = '';
        $lines[] = 'hiermit lade ich euch zur Sitzung ein.';
        $lines[] = '';
        $lines[] = 'Sitzung: ' . (($meetingData['title'] ?? '') ?: 'ohne Titel');
        $lines[] = 'Datum: ' . $this->dateFormatter->germanDate((string)$meetingData['meeting_date']);
        $lines[] = 'Uhrzeit: ' . (($meetingData['meeting_time'] ?? '') ?: 'noch offen');
        $lines[] = 'Ort: ' . (($meetingData['location'] ?? '') ?: 'noch offen');
        $lines[] = '';
        $lines[] = 'Geladene BR-Mitglieder: ' . count(array_filter(
            $recipients,
            static fn(array $recipient): bool => ($recipient['invitation_type'] ?? 'initial') === 'initial'
        ));
        $replacementCount = count(array_filter(
            $recipients,
            static fn(array $recipient): bool => ($recipient['member_role'] ?? '') === 'replacement'
        ));
        if ($replacementCount > 0) {
            $lines[] = 'Geladene Nachrücker*innen: ' . $replacementCount;
        }
        $lines[] = '';
        $lines[] = 'Tagesordnung:';
        $lines[] = '';

        $this->appendAgendaLines($lines, $tops);

        $lines[] = '';
        $lines[] = 'Viele Grüße';
        $lines[] = '{{ABSENDER}}';

        return implode("\n", $lines) . "\n";
    }

    public function markdown(string $subject, string $email): string {
        return "# " . $subject . "\n\n```text\n" . $email . "\n```\n";
    }

    public function recipientList(array $recipients): string {
        $lines = [
            'Ladungsliste',
            '',
            'Snapshot zum Zeitpunkt der Einladung. Spätere Gruppenänderungen verändern diese Liste nicht.',
            '',
        ];

        $summary = $this->recipientSummary($recipients);
        if (count($summary) > 0) {
            foreach ($summary as $line) {
                $lines[] = $line;
            }
            $lines[] = '';
        }

        foreach ($recipients as $recipient) {
            $label = (string)($recipient['display_name'] ?? '');
            if ($label === '') {
                $label = (string)$recipient['user_uid'];
            }

            $line = $recipient['snapshot_position'] . '. ' . $label . ' (' . $recipient['user_uid'] . ')';
            if (($recipient['invitation_type'] ?? 'initial') !== 'absent' && !empty($recipient['email'])) {
                $line .= ' <' . $recipient['email'] . '>';
            }

            $details = $this->recipientDetails($recipient);
            if ($details !== '') {
                $line .= ' - ' . $details;
            }

            $lines[] = $line;
        }

        return implode("\n", $lines) . "\n";
    }

    private function recipientSummary(array $recipients): array {
        $regularCount = 0;
        $replacementCount = 0;
        $confirmedAbsentCount = 0;
        $minorityGender = '';
        $minorityMinimumSeats = 0;
        $attendingMinority = 0;
        $lists = [];

        foreach ($recipients as $recipient) {
            if (($recipient['member_role'] ?? 'regular') === 'replacement') {
                $replacementCount++;
            } else {
                $regularCount++;
            }

            if (($recipient['invitation_type'] ?? '') === 'absent') {
                $confirmedAbsentCount++;
            }

            $minorityGender = $minorityGender ?: (string)($recipient['minority_gender'] ?? '');
            $minorityMinimumSeats = max($minorityMinimumSeats, (int)($recipient['minority_minimum_seats'] ?? 0));
            if (($recipient['invitation_type'] ?? 'initial') !== 'absent'
                && ($recipient['gender'] ?? '') === $minorityGender) {
                $attendingMinority++;
            }
            $listName = (string)($recipient['list_name'] ?? '');
            if ($listName !== '') {
                $lists[$listName] = (int)($recipient['list_seats'] ?? 0);
            }
        }

        $summary = [
            'Feste BR-Mitglieder: ' . $regularCount,
        ];
        if (count($lists) > 0) {
            $listParts = [];
            foreach ($lists as $listName => $seats) {
                $listParts[] = $listName . ($seats > 0 ? ' (' . $seats . ' Sitze)' : '');
            }
            $summary[] = 'Listen: ' . implode(', ', $listParts);
        }
        if ($minorityGender !== '') {
            $summary[] = 'Minderheitenschutz: ' . $attendingMinority
                . ' von mindestens ' . $minorityMinimumSeats . ' Sitzen sichergestellt';
        }
        if ($replacementCount > 0) {
            $summary[] = 'Nachrücker*innen geladen: ' . $replacementCount;
        }
        if ($confirmedAbsentCount > 0) {
            $summary[] = 'Administrativ bestaetigte Verhinderungen: ' . $confirmedAbsentCount;
        }

        return $summary;
    }

    private function recipientDetails(array $recipient): string {
        $details = [];

        if (!empty($recipient['list_name'])) {
            $listDetail = (string)$recipient['list_name'];
            if (!empty($recipient['list_rank'])) {
                $listDetail .= ', Rang ' . (int)$recipient['list_rank'];
            }
            $details[] = $listDetail;
        }

        if (($recipient['member_role'] ?? '') === 'replacement') {
            $details[] = 'Nachrücker*in'
                . (!empty($recipient['replacement_for_name']) ? ' für ' . $recipient['replacement_for_name'] : '');
        }

        if (($recipient['invitation_type'] ?? '') === 'absent') {
            $details[] = 'nicht geladen; Verhinderung administrativ bestaetigt';
        }

        return implode('; ', $details);
    }

    private function appendAgendaLines(array &$lines, array $tops): void {
        if (count($tops) === 0) {
            $lines[] = 'Keine Tagesordnungspunkte vorhanden.';
            return;
        }

        foreach ($tops as $top) {
            $topData = $this->topData($top);
            $indent = str_repeat('  ', max(0, (int)($topData['level'] ?? 1) - 1));
            $line = $indent . $this->agendaService->numberForItem($top) . '. ' . $topData['subject'];

            if (!empty($topData['legal_basis'])) {
                $line .= ' (' . $topData['legal_basis'] . ')';
            }

            $kind = $this->agendaService->itemKind($top);
            if ($kind === 'report') {
                $line .= ' [Bericht]';
            } elseif ($this->agendaService->isResolutionItem($top)) {
                $resolutionCount = max(1, $this->agendaService->resolutionCount($top));
                $line .= $resolutionCount > 1
                    ? ' [' . $resolutionCount . ' Beschlüsse vorgesehen]'
                    : ' [Beschluss vorgesehen]';
            } elseif ($kind === 'discussion') {
                $line .= ' [Beratung]';
            }

            $lines[] = $line;
            $this->appendInvitationDetails($lines, $top, (int)($topData['level'] ?? 1));
        }
    }

    private function appendInvitationDetails(array &$lines, array|AgendaItem $top, int $level): void {
        $topData = $this->topData($top);
        $indent = str_repeat('  ', max(0, $level));
        $note = trim((string)($topData['invitation_note'] ?? ''));
        if ($note !== '') {
            foreach (preg_split('/\R/', $note) ?: [] as $noteLine) {
                $noteLine = trim($noteLine);
                if ($noteLine !== '') {
                    $lines[] = $indent . '- ' . $noteLine;
                }
            }
        }

        $attachments = $this->agendaAttachmentService->attachmentPathLines((string)($topData['attachment_paths'] ?? ''));
        if (count($attachments) > 0) {
            $lines[] = $indent . '- Anhänge: ' . implode('; ', $attachments);
        }
    }

    private function meetingData(array|Meeting $meeting): array {
        return $meeting instanceof Meeting ? $meeting->toRepositoryData() : $meeting;
    }

    private function topData(array|AgendaItem $top): array {
        return $top instanceof AgendaItem ? $top->toRepositoryData() + [
            'created_at' => $top->createdAt,
            'agenda_number' => $top->agendaNumber,
            'protocol_blocks' => array_map(
                fn($block): array => $this->protocolBlockData($block),
                $top->protocolBlocks
            ),
        ] : $top;
    }

    private function protocolBlockData(array|ProtocolBlock $block): array {
        return $block instanceof ProtocolBlock ? $block->toArray() : $block;
    }
}
