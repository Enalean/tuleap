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

use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\TemporaryTestDirectory;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
final class Rule_FileTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use TemporaryTestDirectory;
    use GlobalLanguageMock;
    use ForgeConfigSandbox;

    private array $file;

    protected function setUp(): void
    {
        parent::setUp();
        $tmpName = $this->getTmpDir() . '/_unit_tests_rules_file.txt';
        $fd      = fopen($tmpName, 'w');
        fwrite($fd, 'A test file');
        fclose($fd);
        $this->file = ['name'     => 'File test 1',
            'type'     => 'text/plain',
            'size'     => '11',
            'tmp_name' => $tmpName,
            'error'    => UPLOAD_ERR_OK,
        ];
    }

    public function testOk(): void
    {
        ForgeConfig::set('sys_max_size_upload', 1000);
        $r = new Rule_File();
        self::assertTrue($r->isValid($this->file));
    }

    public function testErrorIniSize(): void
    {
        ForgeConfig::set('sys_max_size_upload', 1000);
        $r = new Rule_File();
        $GLOBALS['Language']
            ->method('getText')
            ->with('rule_file', 'error_upload_size', UPLOAD_ERR_INI_SIZE)
            ->willReturn(UPLOAD_ERR_INI_SIZE);
        $this->file['error'] = UPLOAD_ERR_INI_SIZE;
        self::assertFalse($r->isValid($this->file));
        self::assertMatchesRegularExpression('/' . UPLOAD_ERR_INI_SIZE . '/', $r->error);
    }

    public function testErrorFormSize(): void
    {
        ForgeConfig::set('sys_max_size_upload', 1000);
        $r = new Rule_File();
        $GLOBALS['Language']
            ->method('getText')
            ->with('rule_file', 'error_upload_size', UPLOAD_ERR_FORM_SIZE)
            ->willReturn(UPLOAD_ERR_FORM_SIZE);
        $this->file['error'] = UPLOAD_ERR_FORM_SIZE;
        self::assertFalse($r->isValid($this->file));
        self::assertMatchesRegularExpression('/' . UPLOAD_ERR_FORM_SIZE . '/', $r->error);
    }

    public function testErrorPartial(): void
    {
        ForgeConfig::set('sys_max_size_upload', 1000);
        $r = new Rule_File();
        $GLOBALS['Language']
            ->method('getText')
            ->with('rule_file', 'error_upload_partial', UPLOAD_ERR_PARTIAL)
            ->willReturn(UPLOAD_ERR_PARTIAL);
        $this->file['error'] = UPLOAD_ERR_PARTIAL;
        self::assertFalse($r->isValid($this->file));
        self::assertMatchesRegularExpression('/' . UPLOAD_ERR_PARTIAL . '/', $r->error);
    }

    public function testErrorNoFile(): void
    {
        ForgeConfig::set('sys_max_size_upload', 1000);
        $r = new Rule_File();
        $GLOBALS['Language']
            ->method('getText')
            ->with('rule_file', 'error_upload_nofile', UPLOAD_ERR_NO_FILE)
            ->willReturn(UPLOAD_ERR_NO_FILE);
        $this->file['error'] = UPLOAD_ERR_NO_FILE;
        self::assertFalse($r->isValid($this->file));
        self::assertMatchesRegularExpression('/' . UPLOAD_ERR_NO_FILE . '/', $r->error);
    }

    public function testErrorMaxSize(): void
    {
        ForgeConfig::set('sys_max_size_upload', 5);
        $r = new Rule_File();
        $GLOBALS['Language']
            ->method('getText')
            ->with('rule_file', 'error_upload_size', UPLOAD_ERR_INI_SIZE)
            ->willReturn(UPLOAD_ERR_INI_SIZE);
        self::assertFalse($r->isValid($this->file));
        self::assertMatchesRegularExpression('/' . UPLOAD_ERR_INI_SIZE . '/', $r->error);
    }

    public function testNoName(): void
    {
        ForgeConfig::set('sys_max_size_upload', 1000);
        $r                  = new Rule_File();
        $this->file['name'] = '';
        self::assertFalse($r->isValid($this->file));
    }
}
