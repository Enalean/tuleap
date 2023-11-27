<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Mail;

use Codendi_Mail;
use ForgeConfig;
use org\bovigo\vfs\vfsStream;
use Tuleap\ForgeConfigSandbox;
use Tuleap_Template_Mail;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
final class Codendi_MailTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    public function setUp(): void
    {
        parent::setUp();

        ForgeConfig::set('sys_logger_level', 'debug');
        $tmp_dir = vfsStream::setup();
        ForgeConfig::set('codendi_log', $tmp_dir->url());
    }

    public function testCleanupMailFormat(): void
    {
        $mail = new Codendi_Mail();
        self::assertEquals(['john.doe@example.com', 'Tuleap'], $mail->cleanupMailFormat('"Tuleap" <john.doe@example.com>'));
        self::assertEquals(['john.doe@example.com', 'Tuleap'], $mail->cleanupMailFormat('Tuleap <john.doe@example.com>'));
        self::assertEquals(['"Tuleap" john.doe@example.com', ''], $mail->cleanupMailFormat('"Tuleap" john.doe@example.com'));
        self::assertEquals(['"Tuleap" <john.doe@example.com', ''], $mail->cleanupMailFormat('"Tuleap" <john.doe@example.com'));
        self::assertEquals(['"Tuleap" john.doe@example.com>', ''], $mail->cleanupMailFormat('"Tuleap" john.doe@example.com>'));
    }

    public function testTemplateLookAndFeel(): void
    {
        $body = 'body';

        $tpl = $this->createMock(Tuleap_Template_Mail::class);
        $tpl->expects(self::once())->method('set')->willReturn(['body', $body]);
        $tpl->expects(self::once())->method('fetch');

        $mail = new Codendi_Mail();
        $mail->setLookAndFeelTemplate($tpl);

        $mail->setBodyHtml($body);
    }

    public function testDiscardTemplateLookAndFeel(): void
    {
        $body = 'body';

        $tpl = $this->createMock(Tuleap_Template_Mail::class);
        $tpl->expects(self::never())->method('set');
        $tpl->expects(self::never())->method('fetch');

        $mail = new Codendi_Mail();
        $mail->setLookAndFeelTemplate($tpl);

        $mail->setBodyHtml($body, Codendi_Mail::DISCARD_COMMON_LOOK_AND_FEEL);
    }

    public function testEmptyEmailsAreIgnored(): void
    {
        $mail = new Codendi_Mail();
        $mail->setTo('', true);
        $mail->setCc('', true);
        $mail->setBcc('', true);

        self::assertEmpty($mail->getTo());
        self::assertEmpty($mail->getCc());
        self::assertEmpty($mail->getBcc());
    }

    public function testSpacesOfEmailsAreTrimmed(): void
    {
        $mail = new Codendi_Mail();
        $mail->setTo('    user@example.com  ', true);
        $mail->setCc('    user@example.com  ', true);
        $mail->setBcc('    user@example.com  ', true);

        self::assertSame('user@example.com', $mail->getTo());
        self::assertSame('user@example.com', $mail->getCc());
        self::assertSame('user@example.com', $mail->getBcc());
    }
}
