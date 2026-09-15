<?php

declare(strict_types=1);

namespace OCP {
    if (!interface_exists(IConfig::class)) {
        interface IConfig { public function getAppValue($appName, $key, $default = ""); public function setAppValue($appName, $key, $value); }
    }
}

namespace OCA\BrTop\AppInfo {
    final class Application { public const APP_ID = 'brtop'; }
}

namespace OCA\BrTop\Tests {
    use OCA\BrTop\Service\BrtopSettingsService;
    use OCP\IConfig;

    $config = new class implements IConfig {
        public array $values = ['member_group_name' => 'Legacy BR'];
        public array $writes = [];
        public function getAppValue($appName, $key, $default = ''): string { return $this->values[$key] ?? (string)$default; }
        public function setAppValue($appName, $key, $value): void { $this->values[$key] = (string)$value; $this->writes[] = $key; }
    };
    $service = new BrtopSettingsService($config);

    assertSameValue('Legacy BR', $service->legacyMemberGroupName(), 'The former BRTop value must remain readable solely for one-time migration.');
    assertSameValue(false, array_key_exists('memberGroupName', $service->values()), 'Runtime settings must not expose a second BR group source.');
    $service->save('Sitzung', 2, 5, '10:00', 'BR-Raum', '[]');
    assertSameValue(false, in_array('member_group_name', $config->writes, true), 'Saving BRTop settings must not rewrite the migrated legacy group setting.');

    echo 'BrtopSettingsService smoke tests passed' . PHP_EOL;
}
