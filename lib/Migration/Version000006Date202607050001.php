<?php

declare(strict_types=1);

namespace OCA\BrTop\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version000006Date202607050001 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if ($schema->hasTable('brtop_invitation_recipients')) {
            $table = $schema->getTable('brtop_invitation_recipients');

            if (!$table->hasColumn('member_role')) {
                $table->addColumn('member_role', Types::STRING, ['notnull' => true, 'length' => 32, 'default' => 'regular']);
            }

            if (!$table->hasColumn('invitation_type')) {
                $table->addColumn('invitation_type', Types::STRING, ['notnull' => true, 'length' => 32, 'default' => 'initial']);
            }

            if (!$table->hasColumn('list_name')) {
                $table->addColumn('list_name', Types::STRING, ['notnull' => false, 'length' => 255]);
            }

            if (!$table->hasColumn('list_seats')) {
                $table->addColumn('list_seats', Types::INTEGER, ['notnull' => true, 'default' => 0]);
            }

            if (!$table->hasColumn('list_rank')) {
                $table->addColumn('list_rank', Types::INTEGER, ['notnull' => true, 'default' => 0]);
            }

            if (!$table->hasColumn('gender')) {
                $table->addColumn('gender', Types::STRING, ['notnull' => false, 'length' => 32]);
            }

            if (!$table->hasColumn('minority_gender')) {
                $table->addColumn('minority_gender', Types::STRING, ['notnull' => false, 'length' => 32]);
            }

            if (!$table->hasColumn('absence_reason')) {
                $table->addColumn('absence_reason', Types::STRING, ['notnull' => false, 'length' => 128]);
            }

            if (!$table->hasColumn('absence_excused')) {
                $table->addColumn('absence_excused', Types::STRING, ['notnull' => false, 'length' => 32]);
            }

            if (!$table->hasColumn('replacement_for_uid')) {
                $table->addColumn('replacement_for_uid', Types::STRING, ['notnull' => false, 'length' => 64]);
            }

            if (!$table->hasColumn('replacement_for_name')) {
                $table->addColumn('replacement_for_name', Types::STRING, ['notnull' => false, 'length' => 255]);
            }
        }

        return $schema;
    }
}
