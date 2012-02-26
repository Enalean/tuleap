<?php
/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 * 
 * Originally written by Manuel VACELET, 2006.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * 
 * 
 */

require_once('common/dao/include/DataAccess.class.php');
require_once('common/dao/CodendiDataAccess.class.php');

require_once(dirname(__FILE__).'/../include/Docman_MetadataListOfValuesElementDao.class.php');

Mock::generate('DataAccess');
Mock::generatePartial('Docman_MetadataListOfValuesElementDao', 'MetadataListOfValuesElementDaoTestVersion', array('update', 'retreive', 'prepareRanking'));

class MetadataListOfValuesElementDaoTest extends UnitTestCase {
    
    function testUpdate() {
        // Data
        $metadataId = 1444;
        $valueId = 1125; 
        $name = 'love_value';
        $description = 'desc';
        $rank = 12;
        $status = 'A';

         // Setup
        $da =& new MockDataAccess($this);
        $dao =& new MetadataListOfValuesElementDaoTestVersion($this);
        $dao->setReturnValue('prepareRanking', 15);
        $dao->setReturnValue('update', true);
        $dao->da = new MockDataAccess($this);
        $dao->da->setReturnValue('quoteSmart', "'$name'", array($name));
        $dao->da->setReturnValue('quoteSmart', "'$description'", array($description));
        $dao->da->setReturnValue('quoteSmart', "'$status'", array($status));

        $sql_update = "UPDATE plugin_docman_metadata_love AS love".
            " SET love.name = '".$name."'".
            "  , love.description = '".$description."'".
            "  , love.rank = 15".
            "  , love.status = '".$status."'".
            " WHERE love.value_id = ".$valueId;
        $dao->expectArguments('update', array($sql_update));

        $val = $dao->updateElement($metadataId, $valueId, $name, $description, $rank, $status);
        $this->assertTrue($val);

    }

    function testDeleteByMetadataId() {
        // Data
        $metadataId = 1444;

         // Setup
        $da =& new MockDataAccess($this);
        $dao =& new MetadataListOfValuesElementDaoTestVersion($this);
        $dao->da = new MockDataAccess($this);

        $dao->setReturnValue('update', true);
        $sql_update = "UPDATE plugin_docman_metadata_love AS love SET status = 'D' WHERE value_id IN (  SELECT value_id   FROM plugin_docman_metadata_love_md AS lovemd   WHERE lovemd.field_id = ".$metadataId."     AND lovemd.value_id > 100  )";
        $dao->expectArguments('update', array($sql_update));

        $val = $dao->deleteByMetadataId($metadataId);
        $this->assertTrue($val);
    }
    
}
?>
