<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2004-2011. All rights reserved
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
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class Codendi_MailTest extends TestCase // phpcs:ignore
{
    use MockeryPHPUnitIntegration;

    public function setUp(): void
    {
        parent::setUp();

        ForgeConfig::store();

        ForgeConfig::set('sys_logger_level', 'debug');
        $tmp_dir = vfsStream::setup();
        ForgeConfig::set('codendi_log', $tmp_dir->url());
    }

    public function tearDown(): void
    {
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function testCleanupMailFormat()
    {
        $mail = new Codendi_Mail();
        $this->assertEquals(['john.doe@example.com', 'Tuleap'], $mail->_cleanupMailFormat('"Tuleap" <john.doe@example.com>'));
        $this->assertEquals(['john.doe@example.com', 'Tuleap'], $mail->_cleanupMailFormat('Tuleap <john.doe@example.com>'));
        $this->assertEquals(['"Tuleap" john.doe@example.com', ''], $mail->_cleanupMailFormat('"Tuleap" john.doe@example.com'));
        $this->assertEquals(['"Tuleap" <john.doe@example.com', ''], $mail->_cleanupMailFormat('"Tuleap" <john.doe@example.com'));
        $this->assertEquals(['"Tuleap" john.doe@example.com>', ''], $mail->_cleanupMailFormat('"Tuleap" john.doe@example.com>'));
    }

    public function testTemplateLookAndFeel()
    {
        $body = 'body';

        $tpl = Mockery::mock(Tuleap_Template_Mail::class);
        $tpl->shouldReceive('set')->andReturn(['body', $body])->once();
        $tpl->shouldReceive('fetch')->once();

        $mail = new Codendi_Mail();
        $mail->setLookAndFeelTemplate($tpl);

        $mail->setBodyHtml($body);
    }

    public function testDiscardTemplateLookAndFeel()
    {
        $body = 'body';

        $tpl = Mockery::mock(Tuleap_Template_Mail::class);
        $tpl->shouldReceive('set')->never();
        $tpl->shouldReceive('fetch')->never();

        $mail = new Codendi_Mail();
        $mail->setLookAndFeelTemplate($tpl);

        $mail->setBodyHtml($body, Codendi_Mail::DISCARD_COMMON_LOOK_AND_FEEL);
    }

    public function testEmptyEmailsAreIgnored()
    {
        $mail = new Codendi_Mail();
        $mail->setTo('', true);
        $mail->setCc('', true);
        $mail->setBcc('', true);

        $this->assertEmpty($mail->getTo());
        $this->assertEmpty($mail->getCc());
        $this->assertEmpty($mail->getBcc());
    }

    public function testSpacesOfEmailsAreTrimmed()
    {
        $mail = new Codendi_Mail();
        $mail->setTo('    user@example.com  ', true);
        $mail->setCc('    user@example.com  ', true);
        $mail->setBcc('    user@example.com  ', true);

        $this->assertSame('user@example.com', $mail->getTo());
        $this->assertSame('user@example.com', $mail->getCc());
        $this->assertSame('user@example.com', $mail->getBcc());
    }
}
