<?php

declare(strict_types=1);

namespace OCA\BrTop\Model;

class LegislatureConfiguration {
    private array $data;

    private function __construct(array $data) {
        $this->data = self::normalize($data);
    }

    public static function get(array $payload): self {
        return new self($payload);
    }

    public static function get_all(array $payloads): array {
        return array_map(
            static fn(array $payload): self => self::get($payload),
            $payloads
        );
    }

    public function toArray(): array {
        return $this->data;
    }

    public function save(): never {
        throw new \LogicException('LegislatureConfiguration ist ein DTO und wird ueber LegislatureService gespeichert.');
    }

    private static function normalize(array $data): array {
        $lists = [];
        foreach (($data['lists'] ?? []) as $list) {
            if (!is_array($list)) {
                continue;
            }

            $members = [];
            foreach (($list['members'] ?? []) as $member) {
                if (is_array($member)) {
                    $members[] = $member;
                }
            }
            $list['members'] = $members;
            $lists[] = $list;
        }

        return [
            'id' => isset($data['id']) ? (int)$data['id'] : null,
            'name' => trim((string)($data['name'] ?? '')),
            'starts_on' => trim((string)($data['starts_on'] ?? '')),
            'ends_on' => trim((string)($data['ends_on'] ?? '')),
            'election_type' => trim((string)($data['election_type'] ?? 'list')),
            'council_size' => (int)($data['council_size'] ?? 0),
            'minority_gender' => trim((string)($data['minority_gender'] ?? '')),
            'minority_minimum_seats' => (int)($data['minority_minimum_seats'] ?? 0),
            'absence_calendar_principal' => trim((string)($data['absence_calendar_principal'] ?? '')),
            'absence_calendar_uri' => trim((string)($data['absence_calendar_uri'] ?? '')),
            'status' => trim((string)($data['status'] ?? 'draft')),
            'lists' => $lists,
        ];
    }
}
