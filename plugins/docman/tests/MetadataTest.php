<?php
/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 * 
 * Originally written by Manuel VACELET, 2006.
 * 
 * This file is a part of CodeX.
 * 
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * 
 * $Id$
 */

require_once(dirname(__FILE__).'/../include/Docman_MetadataFactory.class');
require_once(dirname(__FILE__).'/../include/Docman_MetadataValueFactory.class');

require_once(dirname(__FILE__).'/../include/Docman_ItemFactory.class');

Mock::generate('Docman_MetadataDao');

require_once('common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');

require_once('BaseLanguage.class.php');
Mock::generate('BaseLanguage');

class MetadataTest extends UnitTestCase {
    var $groupId;

    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function MetadataTest($name = 'Docman_Metadata test') {
        $this->UnitTestCase($name);
        $this->groupId = 1540;
    }

    function &getHardCodedMetadataList($param) {
        $mdFactory = new Docman_MetadataFactory($this->groupId);
        
        // no status and obso date because require DB access
        $hcmdlabels = array('title', 'description', 'owner'
                            , 'create_date', 'update_date');
        $mdhca = $mdFactory->_buildHardCodedMetadataList($hcmdlabels);

        return $mdhca;
    }

    function &getDao() {
        $dar = new MockDataAccessResult($this);
        $dar->setReturnValue('valid', false);
        $dar->setReturnValueAt(0, 'valid', true);
        $dar->setReturnValueAt(1, 'valid', true);

        $md1 = array('field_id' => 32,
                     'group_id' => $this->groupId,
                     'name' => 'Comments',
                     'data_type' => PLUGIN_DOCMAN_METADATA_TYPE_TEXT,
                     'label' => 'field_32',
                     'description' => 'A set of comments',
                     'required' => false,
                     'empty_ok' => true,
                     'special' => false,
                     'default_value' => '',
                     'use_it' => PLUGIN_DOCMAN_METADATA_USED);

        $md2 = array('field_id' => 75,
                     'group_id' => $this->groupId,
                     'name' => 'Publication date',
                     'data_type' => PLUGIN_DOCMAN_METADATA_TYPE_DATE,
                     'label' => 'field_75',
                     'description' => 'pub date',
                     'required' => false,
                     'empty_ok' => true,
                     'special' => false,
                     'default_value' => '',
                     'use_it' => PLUGIN_DOCMAN_METADATA_USED);

        $dar->setReturnValueAt(0, 'current', $md1);
        $dar->setReturnValueAt(1, 'current', $md2);

        $dao = new MockDocman_MetadataDao($this);
        $dao->setReturnValue('searchByGroupId', $dar);
        return $dao;
    }

    function &_createFromRow($row) {
        $mdFactory = new Docman_MetadataFactory($this->groupId);
        $md =& $mdFactory->_createFromRow($row);
        return $md;
    }

    function &getRealMetadataList($onlyUsed = false, $type = array()) {
        $mda = array();

        $dao =& $this->getDao();
        $dar = $dao->searchByGroupId($this->groupId, $onlyUsed, $type);
        while($dar->valid()) {
            $row =& $dar->current();                        
            
            $mda[] =& $this->_createFromRow($row);

            $dar->next();
        }

        return $mda;
    }


    // Adaptation of Docman_MetadataFactory::appendItemMetadataList
    // to avoid DB queries
    function appendItemMetadataList(&$item) {
        $mda =& $this->getHardCodedMetadataList(true);
        
        $i = 0;
        $nbMd = count($mda);
        while($i < $nbMd) {
            $mda[$i]->setValue($item->getHardCodedMetadataValue($mda[$i]->getLabel()));
            $i++;
        }

        $mdvFactory = new Docman_MetadataValueFactory($this->groupId);
        $mdareal =& $this->getRealMetadataList(true);
        $j = 0;
        $nbMd = count($mdareal);
        while($j < $nbMd) {
            $mdv =& $mdvFactory->getMetadataValue($item, $mdareal[$j]);
            if($mdv === null) {
                // create an empty one
                $mdv = $mdvFactory->newMetadataValue($item->getId(),
                                                     $mdareal[$j]->getId(),
                                                     $mdareal[$j]->getType(),
                                                     null);
            }
            $mdareal[$j]->setValue($mdv->getValue());
            $mda[$i] =& $mdareal[$j];
            $i++;            
            $j++;
        }

        $item->setMetadata($mda);
    }

    /**
     * This test is to verify the behaviour of PHP with multiple arrays,
     * references, objects, ...
     * Manuel Vacelet encountered troubles with these functions with php 4.3.10 that
     * lead to a crash (core dump) of apache.
     */
    function testMetadataCreationFromUserInput() {
        $GLOBALS['Language'] =& new MockBaseLanguage();
        $t = new MockDataAccessResult();
        // Item creation
        $i = array('group_id' => $this->groupId,
                   'parent_id' => 1,
                   'title' => 'Subfolder',
                   'description' => 'One subfolder',
                   'user_id' => 104,
                   'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_LINK,
                   'link_url' => 'http://php.net'/*,
                      'rank' => 
                      'status' => 
                      'obsolescence_date' =>*/);

        $itemFactory = new Docman_ItemFactory();
        $item = $itemFactory->getItemFromRow($i);

        $this->appendItemMetadataList($item);                   

        $mdvFactory = new Docman_MetadataValueFactory($this->groupId);
        
        $metadataArray = array('field_75' => '2006-4-2');

        //------> Original code
        $mdIter =& $item->getMetadataIterator();
        $mdIter->rewind();
        while($mdIter->valid()) {
            $md =& $mdIter->current();
            if(isset($metadataArray[$md->getLabel()])) {
                $mdv = $mdvFactory->newMetadataValue($item->getId(), $md->getId(), $md->getType(), $metadataArray[$md->getLabel()]);
                $md->setValue($mdv->getValue());
            }
            $mdIter->next();
        }
        //<------ Original code
    
        /// TESTS
        $mdIter =& $item->getMetadataIterator();
        $mdIter->rewind();
        while($mdIter->valid()) {
            $md =& $mdIter->current();

            if($md->getId() == 75) {
                $this->assertEqual($md->getValue(), '2006-4-2');
            }

            $mdIter->next();
        }

    }

}
?>
