<?php

declare(strict_types=1);

namespace {
    if (!interface_exists(\OCP\IConfig::class)) {
        eval('namespace OCP; interface IConfig {}');
    }

    require __DIR__ . '/../helpers.php';
    require __DIR__ . '/../../lib/Service/BrtopSettingsService.php';
    require __DIR__ . '/../../lib/Service/MeetingScheduleService.php';

    use OCA\BrTop\Service\BrtopSettingsService;
    use OCA\BrTop\Service\MeetingScheduleService;
    use function OCA\BrTop\Tests\assertSameValue;

    $settings = new class extends BrtopSettingsService {
        public int $regularWeekday = 2;
        public int $invitationWeekday = 5;

        public function __construct() {
        }

        public function defaultMeetingTitle(): string {
            return 'Ordentliche BR-Sitzung';
        }

        public function regularMeetingWeekday(): int {
            return $this->regularWeekday;
        }

        public function invitationWeekday(): int {
            return $this->invitationWeekday;
        }

        public function defaultMeetingTime(): string {
            return '10:30';
        }

        public function defaultLocation(): string {
            return 'BR-Raum';
        }
    };

    $service = new MeetingScheduleService($settings);

    assertSameValue(
        [
            'title' => 'Ordentliche BR-Sitzung',
            'meetingDate' => '2026-07-07',
            'meetingTime' => '10:30',
            'location' => 'BR-Raum',
            'invitationDate' => '2026-07-03',
        ],
        $service->nextRegularMeetingDefaults(new DateTimeImmutable('2026-07-06')),
        'A Monday reference should plan the next Tuesday meeting and previous Friday invitation.'
    );

    assertSameValue(
        '2026-07-14',
        $service->nextRegularMeetingDefaults(new DateTimeImmutable('2026-07-07'))['meetingDate'],
        'A reference on the regular meeting weekday should plan the following week.'
    );

    $settings->regularWeekday = 1;
    $settings->invitationWeekday = 1;
    $sameWeekdayDefaults = $service->nextRegularMeetingDefaults(new DateTimeImmutable('2026-07-06'));
    assertSameValue(
        '2026-07-13',
        $sameWeekdayDefaults['meetingDate'],
        'A same-weekday regular meeting should still be in the following week.'
    );
    assertSameValue(
        '2026-07-06',
        $sameWeekdayDefaults['invitationDate'],
        'A same-weekday invitation should be the previous occurrence, not the meeting day.'
    );

    echo 'MeetingScheduleService smoke tests passed' . PHP_EOL;
}
