<?php

declare(strict_types=1);

namespace OCA\BrTop\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/** Additive template-version history and immutable template references on generated documents. */
final class Version000010Date202610220001 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('brt_doc_templates')) {
            $table = $schema->createTable('brt_doc_templates');
            $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true]);
            $table->addColumn('template_key', Types::STRING, ['length' => 64, 'notnull' => true]);
            $table->addColumn('version_no', Types::INTEGER, ['notnull' => true]);
            $table->addColumn('format', Types::STRING, ['length' => 16, 'notnull' => true]);
            $table->addColumn('content', Types::TEXT, ['length' => 16777215, 'notnull' => true]);
            $table->addColumn('status', Types::STRING, ['length' => 16, 'notnull' => true, 'default' => 'draft']);
            $table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
            $table->addColumn('approved_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['template_key', 'version_no'], 'brt_doc_tpl_key_ver');
            $table->addIndex(['template_key', 'status', 'version_no'], 'brt_doc_tpl_active');
        }

        if ($schema->hasTable('brtop_documents')) {
            $documents = $schema->getTable('brtop_documents');
            if (!$documents->hasColumn('template_key')) {
                $documents->addColumn('template_key', Types::STRING, ['length' => 64, 'notnull' => false]);
            }
            if (!$documents->hasColumn('template_version')) {
                $documents->addColumn('template_version', Types::INTEGER, ['notnull' => false]);
            }
        }

        return $schema;
    }
}
