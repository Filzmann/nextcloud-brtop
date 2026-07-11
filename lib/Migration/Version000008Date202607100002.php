<?php

declare(strict_types=1);

namespace OCA\BrTop\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version000008Date202607100002 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('brtop_absence_reviews')) {
            $table = $schema->createTable('brtop_absence_reviews');
            $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true]);
            $table->addColumn('meeting_id', Types::BIGINT, ['notnull' => true]);
            $table->addColumn('confirmed_by_uid', Types::STRING, ['notnull' => true, 'length' => 64]);
            $table->addColumn('confirmed_at', Types::DATETIME, ['notnull' => true]);
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['meeting_id'], 'brtop_abs_review_meeting');
        }

        if (!$schema->hasTable('brtop_invitation_snapshots')) {
            $table = $schema->createTable('brtop_invitation_snapshots');
            $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true]);
            $table->addColumn('meeting_id', Types::BIGINT, ['notnull' => true]);
            $table->addColumn('legislature_id', Types::BIGINT, ['notnull' => true]);
            $table->addColumn('created_by_uid', Types::STRING, ['notnull' => true, 'length' => 64]);
            $table->addColumn('created_at', Types::DATETIME, ['notnull' => true]);
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['meeting_id'], 'brtop_inv_snapshot_meeting');
        }

        if ($schema->hasTable('brtop_invitation_recipients')) {
            $table = $schema->getTable('brtop_invitation_recipients');
            if (!$table->hasColumn('minority_minimum_seats')) {
                $table->addColumn('minority_minimum_seats', Types::INTEGER, ['notnull' => true, 'default' => 0]);
            }
        }

        return $schema;
    }
}
