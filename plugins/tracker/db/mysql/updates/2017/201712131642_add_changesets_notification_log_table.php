<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
class b201712131642_add_changesets_notification_log_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description()
    {
        return 'Add table tracker_email_notification_log';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS tracker_email_notification_log (
                  changeset_id INT(11) NOT NULL PRIMARY KEY,
                  create_date int(11) NOT NULL,
                  start_date int(11) NULL,
                  end_date int(11) NULL,
                  INDEX idx_end_date( end_date )
                ) ENGINE=InnoDB';

        $this->db->createTable('tracker_email_notification_log', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('tracker_email_notification_log')) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('tracker_email_notification_log table is missing');
        }
    }
}
