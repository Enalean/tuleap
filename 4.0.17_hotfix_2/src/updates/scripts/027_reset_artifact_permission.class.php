<?php
/*
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * 
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('CodendiUpgrade.class.php');

class Update_027 extends CodendiUpgrade {

    function _process() {
        echo $this->getLineSeparator();
        echo "Execution of script : ".get_class($this);
        echo $this->getLineSeparator();
     
        
        $sql = 'SELECT artifact_id FROM artifact LEFT JOIN permissions ON (object_id= cast(artifact_id as char) '.
               'AND permission_type="TRACKER_ARTIFACT_ACCESS") WHERE object_id IS NULL and use_artifact_permissions=1';  
        $res = $this->retrieve($sql);
        if(!$res || $res->isError()) {
            $this->addUpgradeError("An error occured while looking for artifact_id': ".$this->da->isError());
        } else {
            if ($res->rowCount() > 0) {
                foreach ($res as $row) {
                    $sql = 'UPDATE artifact SET use_artifact_permissions=0 WHERE artifact_id='.$this->da->escapeInt($row['artifact_id']);
                    if(!$this->update($sql)) {
                        $this->addUpgradeError("An error occured while updating use_artifact_permission': ".$this->da->isError());
                    }
                }
        
            }
        
        }
        
        echo $this->getLineSeparator();

    }
}
?>
