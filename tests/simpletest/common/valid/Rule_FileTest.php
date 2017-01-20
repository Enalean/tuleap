<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2008.
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
 */

require_once('common/valid/Rule.class.php');

Mock::generatePartial('Rule_File', 'Rule_FileTestVersion', array('geti18nError'));

class Rule_FileTest extends TuleapTestCase {

    function UnitTestCase($name = 'Rule_File test') {
        $this->UnitTestCase($name);
    }

    public function setUp()
    {
        parent::setUp();
        $tmpName = dirname(__FILE__).'/_unit_tests_rules_file.txt';
        $fd = fopen($tmpName, 'w');
        fwrite($fd, 'A test file');
        fclose($fd);
        $this->file = array('name'     => 'File test 1',
                            'type'     => 'text/plain',
                            'size'     => '11',
                            'tmp_name' => $tmpName,
                            'error'    => UPLOAD_ERR_OK);
    }

    public function tearDown()
    {
        @unlink($this->file['tmp_name']);
        unset($this->file);
        parent::tearDown();
    }

    function testOk() {
        $r =& new Rule_FileTestVersion($this);
        $r->setMaxSize('1000');
        $r->setReturnValue('geti18nError', '');
        $this->assertTrue($r->isValid($this->file));
    }

    function testErrorIniSize() {
        $r =& new Rule_FileTestVersion($this);
        $r->setMaxSize('1000');
        $r->setReturnValue('geti18nError', UPLOAD_ERR_INI_SIZE);
        $r->expectOnce('geti18nError', array('error_upload_size', UPLOAD_ERR_INI_SIZE));
        $this->file['error'] = UPLOAD_ERR_INI_SIZE;
        $this->assertFalse($r->isValid($this->file));
        $this->assertPattern('/'.UPLOAD_ERR_INI_SIZE.'/', $r->error);
    }

    function testErrorFormSize() {
        $r =& new Rule_FileTestVersion($this);
        $r->setMaxSize('1000');
        $r->setReturnValue('geti18nError',UPLOAD_ERR_FORM_SIZE );
        $r->expectOnce('geti18nError', array('error_upload_size', UPLOAD_ERR_FORM_SIZE));
        $this->file['error'] = UPLOAD_ERR_FORM_SIZE;
        $this->assertFalse($r->isValid($this->file));
        $this->assertPattern('/'.UPLOAD_ERR_FORM_SIZE.'/', $r->error);
    }

    function testErrorPartial() {
        $r =& new Rule_FileTestVersion($this);
        $r->setMaxSize('1000');
        $r->setReturnValue('geti18nError', UPLOAD_ERR_PARTIAL);
        $r->expectOnce('geti18nError', array('error_upload_partial', UPLOAD_ERR_PARTIAL));
        $this->file['error'] = UPLOAD_ERR_PARTIAL;
        $this->assertFalse($r->isValid($this->file));
        $this->assertPattern('/'.UPLOAD_ERR_PARTIAL.'/', $r->error);
    }

    function testErrorNoFile() {
        $r =& new Rule_FileTestVersion($this);
        $r->setMaxSize('1000');
        $r->setReturnValue('geti18nError', UPLOAD_ERR_NO_FILE);
        $r->expectOnce('geti18nError', array('error_upload_nofile', UPLOAD_ERR_NO_FILE));
        $this->file['error'] = UPLOAD_ERR_NO_FILE;
        $this->assertFalse($r->isValid($this->file));
        $this->assertPattern('/'.UPLOAD_ERR_NO_FILE.'/', $r->error);
    }

    function testErrorMaxSize() {
        $r =& new Rule_FileTestVersion($this);
        $r->setMaxSize('5');
        $r->setReturnValue('geti18nError', UPLOAD_ERR_INI_SIZE);
        $r->expectOnce('geti18nError', array('error_upload_size', UPLOAD_ERR_INI_SIZE));
        $this->assertFalse($r->isValid($this->file));
        $this->assertPattern('/'.UPLOAD_ERR_INI_SIZE.'/', $r->error);
    }

    function testNoName() {
        $r =& new Rule_FileTestVersion($this);
        $r->setMaxSize('1000');
        $r->setReturnValue('geti18nError', '');
        $this->file['name'] = '';
        $this->assertFalse($r->isValid($this->file));
    }
}
