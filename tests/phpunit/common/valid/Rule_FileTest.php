<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\TemporaryTestDirectory;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Rule_FileTest extends TestCase
{
    use MockeryPHPUnitIntegration, TemporaryTestDirectory, GlobalLanguageMock, ForgeConfigSandbox;

    protected function setUp(): void
    {
        parent::setUp();
        $tmpName = $this->getTmpDir() . '/_unit_tests_rules_file.txt';
        $fd = fopen($tmpName, 'w');
        fwrite($fd, 'A test file');
        fclose($fd);
        $this->file = array('name'     => 'File test 1',
                            'type'     => 'text/plain',
                            'size'     => '11',
                            'tmp_name' => $tmpName,
                            'error'    => UPLOAD_ERR_OK);
    }

    public function testOk(): void
    {
        ForgeConfig::set('sys_max_size_upload', 1000);
        $r = new Rule_File();
        $this->assertTrue($r->isValid($this->file));
    }

    public function testErrorIniSize(): void
    {
        ForgeConfig::set('sys_max_size_upload', 1000);
        $r = new Rule_File();
        $GLOBALS['Language']
            ->shouldReceive('getText')
            ->with('rule_file', 'error_upload_size', UPLOAD_ERR_INI_SIZE)
            ->andReturn(UPLOAD_ERR_INI_SIZE);
        $this->file['error'] = UPLOAD_ERR_INI_SIZE;
        $this->assertFalse($r->isValid($this->file));
        $this->assertRegExp('/' . UPLOAD_ERR_INI_SIZE . '/', $r->error);
    }

    public function testErrorFormSize(): void
    {
        ForgeConfig::set('sys_max_size_upload', 1000);
        $r = new Rule_File();
        $GLOBALS['Language']
            ->shouldReceive('getText')
            ->with('rule_file', 'error_upload_size', UPLOAD_ERR_FORM_SIZE)
            ->andReturn(UPLOAD_ERR_FORM_SIZE);
        $this->file['error'] = UPLOAD_ERR_FORM_SIZE;
        $this->assertFalse($r->isValid($this->file));
        $this->assertRegExp('/' . UPLOAD_ERR_FORM_SIZE . '/', $r->error);
    }

    public function testErrorPartial(): void
    {
        ForgeConfig::set('sys_max_size_upload', 1000);
        $r = new Rule_File();
        $GLOBALS['Language']
            ->shouldReceive('getText')
            ->with('rule_file', 'error_upload_partial', UPLOAD_ERR_PARTIAL)
            ->andReturn(UPLOAD_ERR_PARTIAL);
        $this->file['error'] = UPLOAD_ERR_PARTIAL;
        $this->assertFalse($r->isValid($this->file));
        $this->assertRegExp('/' . UPLOAD_ERR_PARTIAL . '/', $r->error);
    }

    public function testErrorNoFile(): void
    {
        ForgeConfig::set('sys_max_size_upload', 1000);
        $r = new Rule_File();
        $GLOBALS['Language']
            ->shouldReceive('getText')
            ->with('rule_file', 'error_upload_nofile', UPLOAD_ERR_NO_FILE)
            ->andReturn(UPLOAD_ERR_NO_FILE);
        $this->file['error'] = UPLOAD_ERR_NO_FILE;
        $this->assertFalse($r->isValid($this->file));
        $this->assertRegExp('/' . UPLOAD_ERR_NO_FILE . '/', $r->error);
    }

    public function testErrorMaxSize(): void
    {
        ForgeConfig::set('sys_max_size_upload', 5);
        $r = new Rule_File();
        $GLOBALS['Language']
            ->shouldReceive('getText')
            ->with('rule_file', 'error_upload_size', UPLOAD_ERR_INI_SIZE)
            ->andReturn(UPLOAD_ERR_INI_SIZE);
        $this->assertFalse($r->isValid($this->file));
        $this->assertRegExp('/' . UPLOAD_ERR_INI_SIZE . '/', $r->error);
    }

    public function testNoName(): void
    {
        ForgeConfig::set('sys_max_size_upload', 1000);
        $r = new Rule_File();
        $this->file['name'] = '';
        $this->assertFalse($r->isValid($this->file));
    }
}
