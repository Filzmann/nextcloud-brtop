<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use OCA\BrTop\Model\AgendaItem;
use OCA\BrTop\Model\Meeting;
use OCA\BrTop\Model\ProtocolBlock;

class DocumentContentService {
    public function __construct(
        private AgendaService $agendaService,
        private InvitationContentService $invitationContentService,
        private DocumentDateFormatter $dateFormatter,
        private DocumentTemplateProvider $templates,
        private BrtopSettingsService $settings
    ) {
    }

    public function invitationSubject(array|Meeting $meeting): string {
        return $this->invitationContentService->subject($meeting);
    }

    public function invitationEmail(array|Meeting $meeting, array $tops, array $recipients = []): string {
        return $this->renderInvitationEmail($meeting, $tops, $recipients)['content'];
    }

    public function renderInvitationEmail(array|Meeting $meeting, array $tops, array $recipients = []): array {
        return $this->templates->renderActive(
            'invitation_email',
            $this->invitationContentService->templateValues($meeting, $tops, $recipients, $this->settings->documentOrganization())
        );
    }

    public function invitationMarkdown(string $subject, string $email): string {
        return $this->renderInvitationMarkdown($subject, $email)['content'];
    }

    public function renderInvitationMarkdown(string $subject, string $email): array {
        return $this->templates->renderActive('invitation_markdown', [
            'SUBJECT' => $subject,
            'EMAIL_BODY' => rtrim($email),
        ]);
    }

    public function invitationRecipientList(array $recipients): string {
        return $this->invitationContentService->recipientList($recipients);
    }

    public function protocolTemplate(array|Meeting $meeting, array $tops): string {
        return $this->renderProtocol($meeting, $tops)['content'];
    }

    public function renderProtocol(array|Meeting $meeting, array $tops): array {
        $organization = $this->settings->documentOrganization();
        return $this->templates->renderActive('protocol_markdown', [
            'COUNCIL_NAME' => (string)$organization['councilName'],
            'COUNCIL_ADDRESS' => (string)$organization['councilAddress'],
            'ISSUE_PLACE' => (string)$organization['issuePlace'],
            'PROTOCOL_BODY' => $this->protocolBody($meeting, $tops),
        ]);
    }

    private function protocolBody(array|Meeting $meeting, array $tops): string {
        $meetingData = $this->meetingData($meeting);
        $lines = [];

        $lines[] = '# Protokollvorlage';
        $lines[] = '';
        $lines[] = '**Sitzung:** ' . $meetingData['title'];
        $lines[] = '**Datum:** ' . $this->dateFormatter->germanDate((string)$meetingData['meeting_date']);
        $lines[] = '**Beginn:** ';
        $lines[] = '**Ende:** ';
        $lines[] = '**Anwesende BR-Mitglieder:** ';
        $lines[] = '**Ersatzmitglieder:** ';
        $lines[] = '**Verhinderte Mitglieder:** ';
        $lines[] = '';
        $lines[] = 'Anwesenheit und ordnungsgemäße Einladung zu den Tagesordnungspunkten werden festgestellt. Der Betriebsrat ist beschlussfähig.';
        $lines[] = '';

        if (count($tops) === 0) {
            $lines[] = '_Keine Tagesordnungspunkte vorhanden._';
        }

        foreach ($tops as $top) {
            $this->appendProtocolTop($lines, $top);
        }

        return implode("\n", $lines) . "\n";
    }

    public function resolutionDocument(array|Meeting $meeting, array|AgendaItem $top, int $resolutionIndex = 1): string {
        return $this->renderResolution($meeting, $top, $resolutionIndex)['content'];
    }

    public function renderResolution(array|Meeting $meeting, array|AgendaItem $top, int $resolutionIndex = 1): array {
        $organization = $this->settings->documentOrganization();
        return $this->templates->renderActive('resolution_markdown', [
            'COUNCIL_NAME' => (string)$organization['councilName'],
            'COUNCIL_ADDRESS' => (string)$organization['councilAddress'],
            'ISSUE_PLACE' => (string)$organization['issuePlace'],
            'RESOLUTION_BODY' => $this->resolutionBody($meeting, $top, $resolutionIndex),
        ]);
    }

    private function resolutionBody(array|Meeting $meeting, array|AgendaItem $top, int $resolutionIndex): string {
        $meetingData = $this->meetingData($meeting);
        $topData = $this->topData($top);
        $lines = [];
        $resolutionCount = max(1, $this->agendaService->resolutionCount($top));

        $lines[] = '# Beschlussdokument';
        $lines[] = '';
        $lines[] = '**Sitzung:** ' . $meetingData['title'];
        $lines[] = '**Datum:** ' . $this->dateFormatter->germanDate((string)$meetingData['meeting_date']);
        $lines[] = '**TOP:** ' . $this->agendaService->numberForItem($top) . ' - ' . $topData['subject'];
        if ($resolutionCount > 1) {
            $lines[] = '**Beschluss:** ' . $resolutionIndex . ' von ' . $resolutionCount;
        }
        $lines[] = '**Verfahren:** ' . $this->agendaService->typeLabel((string)$topData['type']);
        $lines[] = '**Rechtsgrundlage:** ' . (($topData['legal_basis'] ?? '') ?: '-');
        $lines[] = '**Betroffene Person:** ' . (($topData['person_name'] ?? '') ?: '-');
        $lines[] = '';
        $lines[] = '## Beschlussfrage';
        $lines[] = '';
        $questions = $this->resolutionQuestionsForTop($top, $resolutionCount);
        $lines[] = $questions[max(0, min($resolutionCount - 1, $resolutionIndex - 1))];
        $lines[] = '';
        $lines[] = '## Abstimmung';
        $lines[] = '';
        $lines[] = '- Stimmberechtigte Anwesende: ';
        $lines[] = '- Ja-Stimmen: ';
        $lines[] = '- Nein-Stimmen: ';
        $lines[] = '- Enthaltungen: ';
        $lines[] = '';
        $lines[] = '## Ergebnis';
        $lines[] = '';
        $lines[] = 'Der Beschluss wurde angenommen / abgelehnt.';
        $lines[] = '';
        $lines[] = 'Hinweis: Auch bei gemeinsamer Abstimmung wird dieser Fall als eigenes Beschlussdokument geführt.';
        $lines[] = '';
        $lines[] = '## Unterschriften';
        $lines[] = '';
        $lines[] = 'Betriebsratsvorsitz: ___________________________';
        $lines[] = '';
        $lines[] = 'Protokollführung: _____________________________';

        return implode("\n", $lines) . "\n";
    }

    private function appendProtocolTop(array &$lines, array|AgendaItem $top): void {
        $topData = $this->topData($top);
        $kind = $this->agendaService->itemKind($top);
        $level = max(1, min(3, (int)($topData['level'] ?? 1)));
        $heading = str_repeat('#', $level + 1);
        $number = $this->agendaService->numberForItem($top);

        $lines[] = '';
        $lines[] = $heading . ' ' . $number . '. ' . $topData['subject'];

        if ($kind !== 'section') {
            $lines[] = '';
            $lines[] = '**Art:** ' . $this->agendaService->kindLabel($kind);
            $lines[] = '**Einordnung:** ' . $this->agendaService->typeLabel((string)$topData['type']);

            if (!empty($topData['person_name'])) {
                $lines[] = '**Person:** ' . $topData['person_name'];
            }

            if (!empty($topData['legal_basis'])) {
                $lines[] = '**Rechtsgrundlage:** ' . $topData['legal_basis'];
            }
        }

        $content = $this->protocolContentForTop($top);

        if ($kind === 'section') {
            if ($content !== '') {
                $lines[] = '';
                $lines[] = $content;
            }

            return;
        }

        $lines[] = '';

        if ($kind === 'report') {
            $lines[] = '**Bericht:**';
            $lines[] = '';
            $lines[] = $content !== '' ? $content : '> ';
            return;
        }

        $lines[] = '**Beratung:**';
        $lines[] = '';
        $lines[] = $content !== '' ? $content : '> ';

        if ($this->agendaService->isResolutionItem($top)) {
            $resolutionCount = max(1, $this->agendaService->resolutionCount($top));
            $lines[] = '';
            $lines[] = $resolutionCount > 1 ? '**Beschlussfragen:**' : '**Beschlussfrage:**';
            $lines[] = '';
            foreach ($this->resolutionQuestionsForTop($top, $resolutionCount) as $index => $question) {
                $lines[] = $resolutionCount > 1 ? (($index + 1) . '. ' . $question) : $question;
            }
            $lines[] = '';
            $lines[] = '**Abstimmung:**';
            $lines[] = '';
            $lines[] = '- Ja-Stimmen: ';
            $lines[] = '- Nein-Stimmen: ';
            $lines[] = '- Enthaltungen: ';
            $lines[] = '';
            $lines[] = '**Ergebnis:**';
            $lines[] = '';
        }
    }

    private function resolutionQuestionsForTop(array|AgendaItem $top, int $resolutionCount): array {
        $topData = $this->topData($top);
        $raw = trim((string)($topData['resolution_text'] ?? ''));
        $questions = $raw === ''
            ? []
            : array_values(array_filter(array_map('trim', preg_split('/\R/', $raw) ?: []), static fn(string $line): bool => $line !== ''));

        if (count($questions) === 0) {
            $questions[] = $this->agendaService->defaultResolutionText($top);
        }

        while (count($questions) < $resolutionCount) {
            $questions[] = 'Beschlussfrage ' . (count($questions) + 1) . ' ergänzen.';
        }

        return array_slice($questions, 0, $resolutionCount);
    }

    private function protocolContentForTop(array|AgendaItem $top): string {
        $topData = $this->topData($top);
        $blocks = $topData['protocol_blocks'] ?? [];
        if (is_array($blocks) && count($blocks) > 0) {
            $contents = [];
            foreach ($blocks as $block) {
                $blockData = $this->protocolBlockData($block);
                $content = trim((string)($blockData['content'] ?? ''));
                if ($content !== '') {
                    $contents[] = $content;
                }
            }

            return implode("\n\n", $contents);
        }

        return trim((string)($topData['protocol_content'] ?? ''));
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
