<?php

declare(strict_types=1);

namespace OCA\BrTop\View;
use OCP\IL10N;

final class UiComponents {
    /** Lokalisierung fachlicher Texte; ohne IL10N identity. */
    private static function t(?IL10N $l10n, string $text): string
    {
        return $l10n?->t($text) ?? $text;
    }

    private static function presets(?IL10N $l10n = null): array {
        return [
        'newMeeting' => ['icon' => '+', 'label' => self::t($l10n, 'Neue Sitzung'), 'class' => ''],
        'backSessions' => ['icon' => '&larr;', 'label' => self::t($l10n, 'Zurück'), 'class' => ''],
        'createInvitation' => ['icon' => '&#9993;', 'label' => self::t($l10n, 'Ladung erzeugen'), 'class' => ''],
        'editProtocol' => ['icon' => '&#9998;', 'label' => self::t($l10n, 'Protokoll bearbeiten'), 'class' => ''],
        'addTop' => ['icon' => '+', 'label' => self::t($l10n, 'TOP hinzufügen'), 'class' => 'brtop-icon-button-primary'],
        'close' => ['icon' => '&times;', 'label' => self::t($l10n, 'Schließen'), 'class' => ''],
        'saveTop' => ['icon' => '&#10003;', 'label' => self::t($l10n, 'TOP speichern'), 'class' => 'brtop-icon-button-primary'],
        'backDetail' => ['icon' => '&larr;', 'label' => self::t($l10n, 'Zur Sitzung'), 'class' => ''],
        'generateProtocolDocument' => ['icon' => '&#10003;', 'label' => self::t($l10n, 'Protokolldokument erzeugen'), 'class' => ''],
        ];
    }

    public static function preset(string $preset, array $attrs = [], string $class = '', ?IL10N $l10n = null): string {
        if (!isset(self::presets($l10n)[$preset])) {
            throw new \InvalidArgumentException('Unbekanntes BRTop-UI-Preset: ' . $preset);
        }

        $button = self::presets($l10n)[$preset];

        return self::iconButton(
            (string)$button['icon'],
            (string)$button['label'],
            $attrs,
            trim((string)$button['class'] . ' ' . $class)
        );
    }

    public static function iconButton(string $icon, string $label, array $attrs = [], string $class = ''): string {
        $classes = trim('brtop-icon-button ' . $class);
        $attrs = array_merge([
            'type' => 'button',
            'class' => $classes,
            'title' => $label,
            'aria-label' => $label,
        ], $attrs);

        return '<button' . self::attributes($attrs) . '>'
            . '<span class="brtop-icon" aria-hidden="true">' . $icon . '</span>'
            . '<span class="brtop-button-label">' . self::esc($label) . '</span>'
            . '</button>';
    }

    private static function attributes(array $attrs): string {
        $html = '';

        foreach ($attrs as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            $html .= $value === true
                ? ' ' . self::esc((string)$name)
                : ' ' . self::esc((string)$name) . '="' . self::esc((string)$value) . '"';
        }

        return $html;
    }

    private static function esc(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
