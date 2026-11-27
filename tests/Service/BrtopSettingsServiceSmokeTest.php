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

    assertSameValue('Betriebsrat ambulante dienste e.V.', $service->documentOrganization()['councilName'], 'Existing organization values should remain compatible defaults.');
    assertSameValue('Berlin', $service->documentOrganization()['issuePlace'], 'The compatible default issue place should be preserved.');
    $saved = $service->saveDocumentOrganization(
        'Betriebsrat Beispiel GmbH',
        "Beispielweg 1\n12345 Beispielstadt",
        'Beispielstadt',
        '[{"value":"ASA","label":"Arbeitsschutz"},{"value":"IT","label":"IT-Ausschuss"}]',
        'Guten Tag,',
        'hiermit laden wir zur Sitzung ein.',
        'Freundliche Grüße',
        'Der Betriebsrat'
    );
    assertSameValue('Betriebsrat Beispiel GmbH', $saved['councilName'], 'Validated organization settings should round-trip through AppConfig.');
    assertSameValue('IT', $saved['committeeCodes'][1]['value'], 'Validated committee codes should replace the built-in catalog.');
    assertSameValue('IT', $service->normalizeCommitteeCode('it'), 'Configured committee codes should be normalized against the canonical catalog.');

    try {
        $service->saveDocumentOrganization('BR', 'Beispielweg 1', 'Ort', '[{"value":"../IT","label":"IT"}]', 'Hallo', 'Einladung', 'Gruß', 'BR');
        throw new \RuntimeException('Manipulated committee codes must be rejected.');
    } catch (\InvalidArgumentException $e) {
        assertSameValue(true, str_contains($e->getMessage(), 'Ausschusscode'), 'Invalid committee codes should fail with a stable diagnostic.');
    }

    echo 'BrtopSettingsService smoke tests passed' . PHP_EOL;
}
