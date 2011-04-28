<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2008
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */

require_once(dirname(__FILE__).'/../../docman/include/Docman_Item.class.php');
require_once(dirname(__FILE__).'/../../docman/include/Docman_MetadataFactory.class.php');
require_once(dirname(__FILE__).'/../../docman/include/Docman_MetadataListOfValuesElementFactory.class.php');
require_once(dirname(__FILE__).'/../include/DocmanWatermark_MetadataFactory.class.php');
Mock::generate('ArrayIterator');
Mock::generate('Docman_Item');
Mock::generate('Docman_Metadata');
Mock::generate('Docman_MetadataFactory');
Mock::generate('Docman_MetadataListOfValuesElement');
Mock::generate('Docman_MetadataListOfValuesElementFactory');
Mock::generate('DocmanWatermark_MetadataValue');
Mock::generate('DocmanWatermark_MetadataValueFactory');
Mock::generatePartial('DocmanWatermark_MetadataFactory', 
                      'DocmanWatermark_MetadataFactoryTestMetadataRetrieval', 
                      array('getDocmanWatermark_MetadataValueFactory',
                            'getDocman_MetadataListOfValuesElementFactory',
                            'getDocman_MetadataFactory',
                            'getMetadataIdFromGroupId'));
                      
Mock::generatePartial('DocmanWatermark_MetadataFactory', 
                      'DocmanWatermark_MetadataFactoryTestMetadataValueRetrieval', 
                      array('getDocmanWatermark_MetadataValueFactory',
                            'getDocman_MetadataListOfValuesElementFactory',
                            'getDocman_MetadataFactory'));
                      
class DocmanWatermark_MetadataFactoryTest extends UnitTestCase {
    
    function __construct($name = 'DocmanWatermark_MetadataFactoryTest') {
        parent::__construct($name);
    }
    
    function setUp() {
        
    }

    function tearDown() {
        
    }
    
    function testWhenMetaDataNotDefined() {
        $dwmf = new DocmanWatermark_MetadataFactoryTestMetadataRetrieval($this);
        $dwmf->setReturnValue('getMetadataIdFromGroupId', false);
        $dwmf->expectNever('getDocman_MetadataFactory');
        $returnedMd = $dwmf->_getWatermarkedMetadata(101);
        $this->assertNull($returnedMd);
    }

    function testWhenMetaDataDefinedAndAvailable() {
        $md = new MockDocman_Metadata($this);

        $dmdf = new MockDocman_MetadataFactory($this);
        $dmdf->setReturnValue('getLabelFromId', 'field_105');
        $dmdf->setReturnValue('getFromLabel', $md);

        $dwmf = new DocmanWatermark_MetadataFactoryTestMetadataRetrieval($this);
        $dwmf->setReturnValue('getMetadataIdFromGroupId', 105);
        $dwmf->setReturnValue('getDocman_MetadataFactory', $dmdf);

        $returnedMd = $dwmf->_getWatermarkedMetadata(101);
        $this->assertReference($returnedMd, $md);
    }

    function testWhenMetaDataDefinedButNotAvailable() {
        $dmdf = new MockDocman_MetadataFactory($this);
        $dmdf->setReturnValue('getLabelFromId', 'field_105');
        $dmdf->setReturnValue('getFromLabel', null);

        $dwmf = new DocmanWatermark_MetadataFactoryTestMetadataRetrieval($this);
        $dwmf->setReturnValue('getMetadataIdFromGroupId', 105);
        $dwmf->setReturnValue('getDocman_MetadataFactory', $dmdf);

        $returnedMd = $dwmf->_getWatermarkedMetadata(101);
        $this->assertNull($returnedMd);
    }
    
    function testItemWithValueAndValueWatermarked() {        
        // One love
        $love = new MockDocman_MetadataListOfValuesElement($this);
        $love->setReturnValue('getId', 107);
        $love->setReturnValue('getName', 'Confidential');
        
        $loveFactory = new MockDocman_MetadataListOfValuesElementFactory($this);
        // empty array because no value found
        $ai = new MockArrayIterator($this);
        $ai->setReturnValue('rewind', true);
        $ai->setReturnValueAt(0, 'valid', true);
        $ai->setReturnValue('current', $love);
        $ai->setReturnValueAt(1, 'valid', false);
        $loveFactory->setReturnValue('getLoveValuesForItem', $ai);
        
        //
        // But watermarked values is defined
        $dwmv = new MockDocmanWatermark_MetadataValue($this);
        $dwmv->setReturnValue('getWatermark', 1);
        $dwmv->setReturnValue('getValueId', 107);
        
        $ai = new MockArrayIterator($this);
        $ai->setReturnValue('rewind', true);
        $ai->setReturnValueAt(0, 'valid', true);
        $ai->setReturnValue('current', $dwmv);
        $ai->setReturnValueAt(1, 'valid', false);
        
        $dwmvf = new MockDocmanWatermark_MetadataValueFactory($this);
        $dwmvf->setReturnValue('getMetadataValuesIterator', $ai);
        
        // Setup class
        $dwmf = new DocmanWatermark_MetadataFactoryTestMetadataValueRetrieval($this);
        $dwmf->setReturnValue('getDocman_MetadataListOfValuesElementFactory', $loveFactory);
        $dwmf->setReturnValue('getDocmanWatermark_MetadataValueFactory', $dwmvf);
        
        // Parameters & Run
        $item = new MockDocman_Item($this);
        $item->setReturnValue('getId', 1789);
        $md   = new MockDocman_Metadata($this);
        $md->setReturnValue('getId', 1871);
        
        $values = $dwmf->_getWatermarkingValues($item, $md);
        $this->assertIsA($values, 'Array');
        $this->assertIdentical($values[107], $love);
    }
    
    function testItemWithValueButValueNotWatermarked() {        
        // One love
        $love = new MockDocman_MetadataListOfValuesElement($this);
        $love->setReturnValue('getId', 107);
        $love->setReturnValue('getName', 'Confidential');
        
        $loveFactory = new MockDocman_MetadataListOfValuesElementFactory($this);
        // empty array because no value found
        $ai = new MockArrayIterator($this);
        $ai->setReturnValue('rewind', true);
        $ai->setReturnValueAt(0, 'valid', true);
        $ai->setReturnValue('current', $love);
        $ai->setReturnValueAt(1, 'valid', false);
        $loveFactory->setReturnValue('getLoveValuesForItem', $ai);
        
        //
        // But watermarked values is defined
        $dwmv = new MockDocmanWatermark_MetadataValue($this);
        $dwmv->setReturnValue('getWatermark', 0);
        $dwmv->setReturnValue('getValueId', 107);
        
        $ai = new MockArrayIterator($this);
        $ai->setReturnValue('rewind', true);
        $ai->setReturnValueAt(0, 'valid', true);
        $ai->setReturnValue('current', $dwmv);
        $ai->setReturnValueAt(1, 'valid', false);
        
        $dwmvf = new MockDocmanWatermark_MetadataValueFactory($this);
        $dwmvf->setReturnValue('getMetadataValuesIterator', $ai);
        
        // Setup class
        $dwmf = new DocmanWatermark_MetadataFactoryTestMetadataValueRetrieval($this);
        $dwmf->setReturnValue('getDocman_MetadataListOfValuesElementFactory', $loveFactory);
        $dwmf->setReturnValue('getDocmanWatermark_MetadataValueFactory', $dwmvf);
        
        // Parameters & Run
        $item = new MockDocman_Item($this);
        $item->setReturnValue('getId', 1789);
        $md   = new MockDocman_Metadata($this);
        $md->setReturnValue('getId', 1871);
        
        $this->assertNull($dwmf->_getWatermarkingValues($item, $md));
    }
    
    function testItemWithValueButNotWatermarkedValuesDefined() {        
        // One love
        $love = new MockDocman_MetadataListOfValuesElement($this);
        $love->setReturnValue('getId', 107);
        $love->setReturnValue('getName', 'Confidential');
        
        $loveFactory = new MockDocman_MetadataListOfValuesElementFactory($this);
        $ai = new MockArrayIterator($this);
        $ai->setReturnValue('rewind', true);
        $ai->setReturnValueAt(0, 'valid', true);
        $ai->setReturnValue('current', $love);
        $ai->setReturnValueAt(1, 'valid', false);
        $loveFactory->setReturnValue('getLoveValuesForItem', $ai);
        
        $ai = new MockArrayIterator($this);
        $ai->setReturnValue('rewind', true);
        $ai->setReturnValue('valid', false);
        
        $dwmvf = new MockDocmanWatermark_MetadataValueFactory($this);
        $dwmvf->setReturnValue('getMetadataValuesIterator', $ai);
        
        // Setup class
        $dwmf = new DocmanWatermark_MetadataFactoryTestMetadataValueRetrieval($this);
        $dwmf->setReturnValue('getDocman_MetadataListOfValuesElementFactory', $loveFactory);
        $dwmf->setReturnValue('getDocmanWatermark_MetadataValueFactory', $dwmvf);
        
        // Parameters & Run
        $item = new MockDocman_Item($this);
        $item->setReturnValue('getId', 1789);
        $md = new MockDocman_Metadata($this);
        $md->setReturnValue('getId', 1871);
        
        $this->assertNull($dwmf->_getWatermarkingValues($item, $md));
    }
    
    function testItemWithoutValueButWatermarkedValueDefined() {        
        // No love for item
        $loveFactory = new MockDocman_MetadataListOfValuesElementFactory($this);
        // empty array because no value found
        $ai = new MockArrayIterator($this);
        $ai->setReturnValue('rewind', true);
        $ai->setReturnValue('valid', false);
        $loveFactory->setReturnValue('getLoveValuesForItem', $ai);
        
        //
        // But watermarked values is defined
        $dwmv = new MockDocmanWatermark_MetadataValue($this);
        $dwmv->setReturnValue('getWatermark', 1);
        $dwmv->setReturnValue('getValueId', 107);
        
        $ai = new MockArrayIterator($this);
        $ai->setReturnValue('rewind', true);
        $ai->setReturnValueAt(0, 'valid', true);
        $ai->setReturnValue('current', $dwmv);
        $ai->setReturnValueAt(1, 'valid', false);
        
        $dwmvf = new MockDocmanWatermark_MetadataValueFactory($this);
        $dwmvf->setReturnValue('getMetadataValuesIterator', $ai);
        
        // Setup class
        $dwmf = new DocmanWatermark_MetadataFactoryTestMetadataValueRetrieval($this);
        $dwmf->setReturnValue('getDocman_MetadataListOfValuesElementFactory', $loveFactory);
        $dwmf->setReturnValue('getDocmanWatermark_MetadataValueFactory', $dwmvf);
        
        // Parameters & Run
        $item = new MockDocman_Item($this);
        $item->setReturnValue('getId', 1789);
        $md   = new MockDocman_Metadata($this);
        $md->setReturnValue('getId', 1871);
        
        $this->assertNull($dwmf->_getWatermarkingValues($item, $md));
    }
}
?>
