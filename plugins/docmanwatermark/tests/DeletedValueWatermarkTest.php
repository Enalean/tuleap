<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 * 
 * Originally written by Mahmoud MAALEJ, 2009.
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
 * 
 */

require_once(dirname(__FILE__).'/../../docman/include/Docman_Metadata.class.php');
require_once(dirname(__FILE__).'/../../docman/include/Docman_MetadataListOfValuesElement.class.php');
require_once(dirname(__FILE__).'/../include/DocmanWatermark_Stamper.class.php');
Mock::generate('Docman_Metadata');
Mock::generate('Docman_MetadataListOfValuesElement');
Mock::generatePartial('DocmanWatermark_Stamper', 
                      'DocmanWatermark_StamperTest', 
                      array('getMetadataIdForWatermark',
                            'getMetadataForWatermark',
                            'getItemValueForWatermark',
                            'isWatermarkedOnValue',
                            'getHeaders'
                      ));

class DeletedValueWatermark extends UnitTestCase {
    
    function DeletedValueWatermark($name = 'Deleted value Test') {
        $this->UnitTestCase($name);
    }
    
    function setUp() {
        
    }

    function tearDown() {
        
    }
    
    function testWatermarkWhenMetaDataNotAvailable() {
        $dws =& new DocmanWatermark_StamperTest($this);
        $dws->setReturnValue('getMetadataIdForWatermark', 0);
        $dws->expectNever('getMetadataForWatermark');
        $check = $dws->check();
        $this->assertEqual($check, false);
    }
    
    function testWatermarkWhenItemIsNotPDF() {
        $dws =& new DocmanWatermark_StamperTest($this);
        $dws->setReturnValue('getMetadataIdForWatermark', 10);
        
        $md = new MockDocman_Metadata($this);
        $md->setReturnValue('getId', 10);
        $md->setReturnValue('getName', 'Watermark');
        $dws->setReturnReference('getMetadataForWatermark', $md);
        
        $mdv = new MockDocman_MetadataListOfValuesElement();
        $mdv->setId(1);
        $mdv->setName('value1');
        $dws->setReturnReference('getItemValueForWatermark', $mdv);
        
        $dws->setReturnValue('isWatermarkedOnValue', true);
        $dws->setReturnValue('getHeaders', array('mime_type' => 'text/html'));
        $check = $dws->check();
        $this->assertEqual($check, false);
    }
}
?>
