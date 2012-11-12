<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'V3/Dao.class.php';
require_once 'V3/FieldsetsDao.class.php';
require_once 'V3/FieldsDao.class.php';
require_once 'V3/FieldsDefaultValuesDao.class.php';
require_once 'V3/ReportsDao.class.php';
require_once 'V3/RenderersTableDao.class.php';
require_once 'V3/RenderersGraphDao.class.php';
require_once 'V3/PermissionsOnArtifactFieldDao.class.php';
require_once 'V3/AttachmentFieldDao.class.php';
require_once 'V3/ReferenceFieldDao.class.php';
require_once 'V3/SemanticDao.class.php';
require_once 'V3/CannedDao.class.php';
require_once 'V3/CcFieldDao.class.php';
require_once 'V3/ColumnsDao.class.php';
require_once 'V3/FieldPermsDao.class.php';
require_once 'V3/FieldDependenciesDao.class.php';

/**
 * This migrate trackers v3 into tracker v5
 */
class Tracker_Migration_V3 {

    /** @var TrackerFactory */
    private $tracker_factory;

    public function __construct(TrackerFactory $tracker_factory) {
        $this->tracker_factory = $tracker_factory;
    }

    /**
     * @return Tracker (only the structure)
     */
    public function createTV5FromTV3(Project $project, $name, $description, $itemname, ArtifactType $tv3) {
        $dao = new Tracker_Migration_V3_Dao();
        $logger = new Zend_Log();
        $writer = new Zend_Log_Writer_Stream(Config::get('codendi_log').'/tv3_to_tv5.log');
        $logger->addWriter($writer);

        $log_prefix = '['.uniqid().']';
        $logger->info("$log_prefix Start migration of tracker v3: ".$tv3->getID());
        // 010 & 020
        if ($id = $dao->create($project->getId(), $name, $description, $itemname, $tv3->getID())) {
            $logger->info("$log_prefix Tracker v5: ".$id);

            $logger->info("$log_prefix 030 Fieldset");
            $fieldset_dao = new Tracker_Migration_V3_FieldsetsDao();
            $fieldset_dao->create($tv3->getID(), $id);

            $logger->info("$log_prefix 040 Fields");
            $field_dao = new Tracker_Migration_V3_FieldsDao();
            $field_dao->create($tv3->getID(), $id);

            $logger->info("$log_prefix 045 & 046 Fields Default Values");
            $fields_default_values_dao = new Tracker_Migration_V3_FieldsDefaultValuesDao();
            $fields_default_values_dao->create($tv3->getID(), $id);

            $logger->info("$log_prefix 050 Reports");
            $reports_dao = new Tracker_Migration_V3_ReportsDao();
            $reports_dao->create($tv3->getID(), $id, $project->getId());

            $logger->info("$log_prefix 060 RenderersTable");
            $renderers_table_dao = new Tracker_Migration_V3_RenderersTableDao();
            $renderers_table_dao->create($tv3->getID(), $id);
            
            $logger->info("$log_prefix 070 RenderersGraph");
            $renderers_graph_dao = new Tracker_Migration_V3_RenderersGraphDao();
            $renderers_graph_dao->create($tv3->getID(), $id);

            $logger->info("$log_prefix 075 PermissionsOnArtifactField");
            $perms_on_artifact_dao = new Tracker_Migration_V3_PermissionsOnArtifactFieldDao();
            $perms_on_artifact_dao->addPermissionsOnArtifactField($id);

            $logger->info("$log_prefix 080 AttachmentField");
            $attachment_field_dao = new Tracker_Migration_V3_AttachmentFieldDao();
            $attachment_field_dao->addAttachmentField($id);

            $logger->info("$log_prefix 085 ReferenceField");
            $reference_dao = new Tracker_Migration_V3_ReferenceFieldDao();
            $reference_dao->addReferenceField($id);

            $logger->info("$log_prefix 090 CCField");
            $cc_dao = new Tracker_Migration_V3_CcFieldDao();
            $cc_dao->addCCField($id);

            $logger->info("$log_prefix 220 Semantic");
            $semantic_dao = new Tracker_Migration_V3_SemanticDao();
            $semantic_dao->create($id);

            $logger->info("$log_prefix 250 Canned");
            $canned_dao = new Tracker_Migration_V3_CannedDao();
            $canned_dao->create($tv3->getID(), $id);

            $logger->info("$log_prefix 260 Fieldset stored as field");
            $fieldset_dao->nowFieldsetsAreStoredAsField($id);

            $logger->info("$log_prefix 270 & 280 columns");
            $columns_dao = new Tracker_Migration_V3_ColumnsDao();
            $columns_dao->create($id);

            // 300
            // useless because transitions already have default permissions

            $logger->info("$log_prefix 310 FieldPerms");
            $field_perms_dao = new Tracker_Migration_V3_FieldPermsDao();
            $field_perms_dao->create($tv3->getID(), $id);

            $logger->info("$log_prefix 320 FieldDependencies");
            $field_dependencies_dao = new Tracker_Migration_V3_FieldDependenciesDao();
            $field_dependencies_dao->addDependencies($tv3->getID(), $id);

            $logger->info("$log_prefix Complete");
            return $this->tracker_factory->getTrackerById($id);
        }
    }
}
?>
