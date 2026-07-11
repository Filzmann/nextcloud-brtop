<?php

declare(strict_types=1);

namespace OCA\BrTop\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version000007Date202607100001 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('brtop_legislatures')) {
            $table = $schema->createTable('brtop_legislatures');
            $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true]);
            $table->addColumn('name', Types::STRING, ['notnull' => true, 'length' => 255]);
            $table->addColumn('starts_on', Types::STRING, ['notnull' => true, 'length' => 10]);
            $table->addColumn('ends_on', Types::STRING, ['notnull' => true, 'length' => 10]);
            $table->addColumn('election_type', Types::STRING, ['notnull' => true, 'length' => 32, 'default' => 'list']);
            $table->addColumn('council_size', Types::INTEGER, ['notnull' => true]);
            $table->addColumn('minority_gender', Types::STRING, ['notnull' => true, 'length' => 32]);
            $table->addColumn('minority_minimum_seats', Types::INTEGER, ['notnull' => true]);
            $table->addColumn('absence_calendar_principal', Types::STRING, ['notnull' => false, 'length' => 255]);
            $table->addColumn('absence_calendar_uri', Types::STRING, ['notnull' => false, 'length' => 255]);
            $table->addColumn('status', Types::STRING, ['notnull' => true, 'length' => 32, 'default' => 'draft']);
            $table->addColumn('created_by_uid', Types::STRING, ['notnull' => true, 'length' => 64]);
            $table->addColumn('created_at', Types::DATETIME, ['notnull' => true]);
            $table->addColumn('activated_at', Types::DATETIME, ['notnull' => false]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['starts_on', 'ends_on'], 'brtop_legislature_dates');
            $table->addIndex(['status'], 'brtop_legislature_status');
        }

        if (!$schema->hasTable('brtop_electoral_lists')) {
            $table = $schema->createTable('brtop_electoral_lists');
            $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true]);
            $table->addColumn('legislature_id', Types::BIGINT, ['notnull' => true]);
            $table->addColumn('name', Types::STRING, ['notnull' => true, 'length' => 255]);
            $table->addColumn('seat_count', Types::INTEGER, ['notnull' => true]);
            $table->addColumn('vote_count', Types::INTEGER, ['notnull' => true]);
            $table->addColumn('sort_order', Types::INTEGER, ['notnull' => true]);
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['legislature_id', 'name'], 'brtop_list_name_unique');
            $table->addUniqueIndex(['legislature_id', 'sort_order'], 'brtop_list_order_unique');
        }

        if (!$schema->hasTable('brtop_roster_members')) {
            $table = $schema->createTable('brtop_roster_members');
            $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true]);
            $table->addColumn('legislature_id', Types::BIGINT, ['notnull' => true]);
            $table->addColumn('list_id', Types::BIGINT, ['notnull' => true]);
            $table->addColumn('user_uid', Types::STRING, ['notnull' => true, 'length' => 64]);
            $table->addColumn('display_name', Types::STRING, ['notnull' => true, 'length' => 255]);
            $table->addColumn('email', Types::STRING, ['notnull' => false, 'length' => 255]);
            $table->addColumn('gender', Types::STRING, ['notnull' => true, 'length' => 32]);
            $table->addColumn('member_role', Types::STRING, ['notnull' => true, 'length' => 32]);
            $table->addColumn('list_rank', Types::INTEGER, ['notnull' => true]);
            $table->addColumn('active', Types::BOOLEAN, ['notnull' => true, 'default' => true]);
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['legislature_id', 'user_uid'], 'brtop_roster_uid_unique');
            $table->addUniqueIndex(['list_id', 'list_rank'], 'brtop_roster_rank_unique');
            $table->addIndex(['legislature_id', 'member_role'], 'brtop_roster_role');
        }

        if (!$schema->hasTable('brtop_meeting_absences')) {
            $table = $schema->createTable('brtop_meeting_absences');
            $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true]);
            $table->addColumn('meeting_id', Types::BIGINT, ['notnull' => true]);
            $table->addColumn('member_id', Types::BIGINT, ['notnull' => true]);
            $table->addColumn('status', Types::STRING, ['notnull' => true, 'length' => 32, 'default' => 'suggested']);
            $table->addColumn('source', Types::STRING, ['notnull' => true, 'length' => 32, 'default' => 'manual']);
            $table->addColumn('confirmed_by_uid', Types::STRING, ['notnull' => false, 'length' => 64]);
            $table->addColumn('updated_at', Types::DATETIME, ['notnull' => true]);
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['meeting_id', 'member_id'], 'brtop_meeting_abs_unique');
            $table->addIndex(['meeting_id', 'status'], 'brtop_meeting_abs_status');
        }

        if ($schema->hasTable('brtop_meetings')) {
            $table = $schema->getTable('brtop_meetings');
            if (!$table->hasColumn('legislature_id')) {
                $table->addColumn('legislature_id', Types::BIGINT, ['notnull' => false]);
                $table->addIndex(['legislature_id'], 'brtop_meeting_legislature');
            }
        }

        if ($schema->hasTable('brtop_invitation_recipients')) {
            $table = $schema->getTable('brtop_invitation_recipients');
            if (!$table->hasColumn('legislature_id')) {
                $table->addColumn('legislature_id', Types::BIGINT, ['notnull' => false]);
            }
            if (!$table->hasIndex('brtop_inv_rec_position')) {
                $table->addUniqueIndex(['meeting_id', 'snapshot_position'], 'brtop_inv_rec_position');
            }
        }

        return $schema;
    }
}
