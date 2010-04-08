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

class Update_026 extends CodendiUpgrade {

    function _process() {
        echo $this->getLineSeparator();
        echo "Execution of script : ".get_class($this);
        echo $this->getLineSeparator();
        
           
        $sql = ' ALTER TABLE plugin_statistics_user_session TYPE= INNODB';
        if(!$this->update($sql)) {
            $this->addUpgradeError("An error occured while changing plugin_statistics_user_session to innoDB table': ".$this->da->isError());
        }

        $sql = ' ALTER TABLE  plugin_statistics_diskusage_group TYPE= INNODB';
        if(!$this->update($sql)) {
            $this->addUpgradeError("An error occured while changing plugin_statistics_diskusage_group to innoDB table': ".$this->da->isError());
        }
        
        $sql = ' ALTER TABLE plugin_statistics_diskusage_user TYPE= INNODB';
        if(!$this->update($sql)) {
            $this->addUpgradeError("An error occured while changing plugin_statistics_diskusage_user to innoDB table': ".$this->da->isError());
        }

        $sql = ' ALTER TABLE plugin_statistics_diskusage_site TYPE= INNODB';
        if(!$this->update($sql)) {
            $this->addUpgradeError("An error occured while changing plugin_statistics_diskusage_site to innoDB table': ".$this->da->isError());
        }
        
        echo $this->getLineSeparator();
        
    }
}
?>
