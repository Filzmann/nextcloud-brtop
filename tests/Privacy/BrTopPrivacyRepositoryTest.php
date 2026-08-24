<?php

declare(strict_types=1);

namespace OCP { interface IDBConnection { public function getQueryBuilder(); } }

namespace {
    use OCA\BrTop\Privacy\BrTopPrivacyRepository;
    use OCP\IDBConnection;

    final class PrivacyResult {
        public function __construct(private array $rows) {}
        public function fetchAll(): array { return $this->rows; }
    }
    final class PrivacyExpression { public function eq(string $left, mixed $right): array { return [$left, $right]; } }
    final class PrivacyBuilder {
        public array $selected = [];
        public array $bindings = [];
        public string $table = '';
        public ?int $limit = null;
        public function __construct(private array $rows) {}
        public function select(string ...$columns): self { $this->selected = $columns; return $this; }
        public function from(string $table, ?string $alias = null): self { $this->table = $table; return $this; }
        public function innerJoin(string $fromAlias, string $join, string $alias, mixed $condition): self { return $this; }
        public function where(mixed $condition): self { return $this; }
        public function orderBy(string $column, string $direction): self { return $this; }
        public function setMaxResults(int $limit): self { $this->limit = $limit; return $this; }
        public function createNamedParameter(mixed $value): array { $this->bindings[] = $value; return ['value'=>$value]; }
        public function expr(): PrivacyExpression { return new PrivacyExpression(); }
        public function executeQuery(): PrivacyResult { return new PrivacyResult($this->rows); }
    }
    final class PrivacyConnection implements IDBConnection {
        public array $builders = [];
        public function getQueryBuilder(): PrivacyBuilder {
            $builder = new PrivacyBuilder([['id'=>count($this->builders)+1,'legislature_id'=>1,'display_name'=>'Test','email'=>'','gender'=>'','member_role'=>'regular','list_rank'=>1,'active'=>true,'meeting_date'=>'2026-08-25','meeting_type'=>'regular','invitation_type'=>'initial','absence_reason'=>'','absence_excused'=>'','created_at'=>'2026-08-24','status'=>'draft','invitation_status'=>'created','document_type'=>'protocol_odt','confirmed_at'=>'2026-08-24']]);
            $this->builders[] = $builder;
            return $builder;
        }
    }

    $connection = new PrivacyConnection();
    $records = (new BrTopPrivacyRepository($connection))->forSubject('self', 20);
    if (array_column($records, 'kind') !== ['membership','invitation','meeting','document','activity','activity','activity']) {
        throw new RuntimeException('BRTop-Privacy-Repository verliert eine explizite UID-Datenklasse.');
    }
    if (count($connection->builders) !== 7) throw new RuntimeException('BRTop-Privacy-Repository führt unerwartete Datenzugriffe aus.');
    foreach ($connection->builders as $builder) {
        if ($builder->bindings !== ['self'] || $builder->limit === null || $builder->limit < 1) {
            throw new RuntimeException('Eine BRTop-Privacy-Abfrage ist nicht an Subject und Limit gebunden.');
        }
        foreach (['title','location','file_path','content','protocol_content','attachment_paths','replacement_for_uid'] as $forbidden) {
            if (in_array($forbidden, $builder->selected, true) || in_array('d.' . $forbidden, $builder->selected, true) || in_array('r.' . $forbidden, $builder->selected, true)) {
                throw new RuntimeException("BRTop-Privacy-Abfrage liest ausgeschlossene Inhalte: {$forbidden}");
            }
        }
    }

    echo "BRTop privacy repository test passed\n";
}
