<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use OCA\BrTop\Repository\MeetingRepository;
use OCP\IL10N;

/**
 * Hängt den TOP „Bericht aus den Sprechstunden“ samt Sub-TOP je Sprechstunde
 * (Metadaten aus dem brsprechstundenbuch-V1-Vertrag) an eine Sitzung an.
 * Ohne verfügbaren Provider bleibt die Sitzung unverändert (null-Snapshot).
 */
class SprechstundenberichtService
{
    public function __construct(
        private SprechstundenberichtGateway $gateway,
        private AgendaService $agendaService,
        private MeetingRepository $meetingRepository,
        private ?IL10N $l10n = null,
    )
    {
    }

    private function t(string $text, array $parameters = []): string
    {
        if ($this->l10n !== null)
        {
            return $this->l10n->t($text, $parameters);
        }
        $replacements = [];
        foreach ($parameters as $key => $value)
        {
            $replacements['{' . $key . '}'] = (string)$value;
        }
        return strtr($text, $replacements);
    }

    /**
     * @return int Anzahl der angelegten Sub-TOPs (0 wenn kein Bericht vorliegt)
     */
    public function appendToMeeting(int $meetingId, string $uid): int
    {
        $meeting = $this->meetingRepository->findById($meetingId);
        if ($meeting === null)
        {
            return 0;
        }
        $since = $this->previousMeetingDate((string)$meeting['meeting_date']);
        $snapshot = $this->gateway->snapshotSince($uid, $since);
        if ($snapshot === null || ($snapshot['sprechstunden'] ?? []) === [])
        {
            return 0;
        }
        $parentId = $this->agendaService->addItem(
            $meetingId,
            'section',
            $this->t('Bericht aus den Sprechstunden'),
            '',
            '',
            '',
            false,
            'section',
            0,
            '',
        );
        $count = 0;
        foreach ($snapshot['sprechstunden'] as $sprechstunde)
        {
            $subject = $this->t('Sprechstundenbericht {datum} ({mitglied})', [
                'datum' => (string)$sprechstunde['date'],
                'mitglied' => (string)$sprechstunde['member_uid'],
            ]);
            $protocol = $this->t('Sprechstunde vom {datum} ({mitglied}): {eintraege} Einträge ({wichtige} wichtig), {faelle} Einzelfälle.', [
                'datum' => (string)$sprechstunde['date'],
                'mitglied' => (string)$sprechstunde['member_uid'],
                'eintraege' => (int)$sprechstunde['eintraege'],
                'wichtige' => (int)$sprechstunde['wichtige'],
                'faelle' => (int)$sprechstunde['faelle'],
            ]);
            $this->agendaService->addItem(
                $meetingId,
                'report',
                $subject,
                (string)$sprechstunde['member_uid'],
                '',
                '',
                false,
                'report',
                $parentId,
                $protocol,
            );
            $count++;
        }
        return $count;
    }

    /** Datum der letzten Sitzung vor dem gegebenen Datum (Stichtag für den Bericht). */
    private function previousMeetingDate(string $beforeDate): string
    {
        $latest = '1970-01-01';
        foreach ($this->meetingRepository->findRecent(50) as $meeting)
        {
            $date = (string)($meeting['meeting_date'] ?? '');
            if ($date !== '' && $date < $beforeDate && $date > $latest)
            {
                $latest = $date;
            }
        }
        return $latest;
    }
}
