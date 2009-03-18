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

require_once(dirname(__FILE__).'/../include/DocmanWatermark_Stamper.class.php');
Mock::generatePartial('DocmanWatermark_Stamper', 
                      'DocmanWatermark_StamperTest', 
                      array('getMetadataIdForWatermark',
                            'getMetadataForWatermark',
                            'getValueForWatermark',
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
    
    function testWatermarkWhenMetaDataAvailableAndItemIsPDF() {
        $dws =& new DocmanWatermark_StamperTest($this);
        $dws->setReturnValue('getMetadataIdForWatermark', 1);
        $dws->setReturnValue('getItemValueForWatermark', 'watermark');
        $dws->setReturnValue('isWatermarkedOnValue', true);
        $dws->setReturnValue('getHeaders', array('mime_type' => 'application/pdf'));
        $check = $dws->check();
        $this->assertEqual($check, true);
    }
    
    /*function testWatermarkWhenItemIsNotPDF() {
        $dws =& new DocmanWatermark_StamperTest($this);
        $dws->setReturnValue('getMetadataIdForWatermark', 1);
        $dws->setReturnValue('getMetadataForWatermark', 'Metadata');
        $dws->setReturnValue('getItemValueForWatermark', 'watermark');
        $dws->setReturnValue('isWatermarkedOnValue', true);
        $dws->setReturnValue('getHeaders', array('mime_type' => 'text/html'));
        $check = $dws->check();
        $this->assertEqual($check, false);
    }
    
    function testWatermarkWhenDeletedValue() {
        $dws =& new DocmanWatermark_StamperTest($this);
        $dws->setReturnValue('getMetadataIdForWatermark', 1);
        $dws->setReturnValue('getMetadataForWatermark', 'Metadata');
        $dws->setReturnValue('getItemValueForWatermark', 'watermark');
        $dws->setReturnValue('isWatermarkedOnValue', false);
        $dws->setReturnValue('getHeaders', array('mime_type' => 'application/pdf'));
        require_once(dirname(__FILE__).'/../../docman/include/Docman_MetadataListOfValuesElement.class.php');
        $value = new Docman_MetadataListOfValuesElement();
        // value deleted
        $value->setId(0);
        $value->setName('Sample Value');
        $check = $dws->check();
        $this->assertEqual($check, true);
    }*/
    
}
?>
