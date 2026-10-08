<?php

declare(strict_types=1);

namespace OCA\BrTop\Settings;

use OCA\BrTop\AppInfo\Application;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

final class AdminSection implements IIconSection {
    public function __construct(private IURLGenerator $url) {}
    public function getIcon(): string { return $this->url->imagePath(Application::APP_ID, 'app.svg'); }
    public function getID(): string { return Application::APP_ID; }
    public function getName(): string { return 'BR TOP'; }
    public function getPriority(): int { return 65; }
}
