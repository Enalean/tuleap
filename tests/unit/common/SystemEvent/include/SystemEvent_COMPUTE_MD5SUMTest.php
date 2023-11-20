<?php
/**
 * Copyright (c) Enalean, 2012—Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2004-2010. All rights reserved
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

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
final class SystemEvent_COMPUTE_MD5SUMTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var PHPUnit\Framework\MockObject\MockObject&SystemEvent_COMPUTE_MD5SUM
     */
    private $evt;
    /**
     * @var BaseLanguage&PHPUnit\Framework\MockObject\MockObject
     */
    private $language;

    protected function setUp(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $this->evt = $this->getMockBuilder(\SystemEvent_COMPUTE_MD5SUM::class)
            ->setConstructorArgs(
                [
                    '1',
                    SystemEvent::TYPE_COMPUTE_MD5SUM,
                    SystemEvent::OWNER_ROOT,
                    '100012',
                    SystemEvent::PRIORITY_MEDIUM,
                    SystemEvent::STATUS_RUNNING,
                    $now,
                    $now,
                    $now,
                    '',
                ]
            )
            ->onlyMethods([
                'getFileFactory',
                'getBaseLanguageFactory',
                'getUser',
                'computeFRSMd5Sum',
                'updateDB',
                'compareMd5Checksums',
                'sendNotificationMail',
                'done',
            ])
            ->getMock();

        $file = $this->createMock(\FRSFile::class);
        $file->method('getFileLocation')->willReturn('/var/lib/tuleap/ftp/tuleap/project_1/p2952_r10819/test.dump');
        $file->method('getUserID')->willReturn(142);
        $file->method('getFileName')->willReturn('test.dump');

        $file_factory = $this->createMock(FRSFileFactory::class);
        $file_factory->method('getFRSFileFromDB')->with('100012')->willReturn($file);

        $user = $this->createMock(\PFUser::class);
        $user->method('getEmail')->willReturn('mickey@example.com');
        $user->method('getLocale')->willReturn('fr_FR');

        $base_language_factory = $this->createMock(BaseLanguageFactory::class);
        $base_language_factory
            ->expects(self::once())
            ->method('getBaseLanguage')
            ->with('fr_FR')
            ->willReturn($this->language);

        $this->evt->method('getFileFactory')->willReturn($file_factory);
        $this->evt->method('getBaseLanguageFactory')->willReturn($base_language_factory);
        $this->evt->method('getUser')->willReturn($user);
    }

    /**
     * Compute md5sum
     */
    public function testComputeMd5sumSucceed(): void
    {
        $this->evt->method('computeFRSMd5Sum')->willReturn('d41d8cd98f00b204e9800998ecf8427e');
        $this->evt->method('updateDB')->willReturn(true);

        //Checksum comparison
        $this->evt->method('compareMd5Checksums')->willReturn(true);
        // Expect everything went OK
        $this->evt->method('sendNotificationMail')->willReturn(false);
        $this->evt->expects(self::once())->method('done');

        // Launch the event
        self::assertTrue($this->evt->process());
    }

    public function testComputeMd5sumFailure(): void
    {
        $this->evt->method('computeFRSMd5Sum')->willReturn(false);

        $this->evt->method('sendNotificationMail')->willReturn(true);

        $this->evt->expects(self::never())->method('done');

        self::assertFalse($this->evt->process());

        // Check errors
        self::assertEquals(SystemEvent::STATUS_ERROR, $this->evt->getStatus());
        self::assertEquals('Computing md5sum failed', $this->evt->getLog());
    }

    public function testComputeMd5sumUpdateDBFailure(): void
    {
        $this->evt->method('computeFRSMd5Sum')->willReturn('d41d8cd98f00b204e9800998ecf8427e');

        // DB
        $this->evt->method('updateDB')->willReturn(false);

        $this->evt->expects(self::never())->method('done');
        self::assertFalse($this->evt->process());

        // Check errors
        self::assertEquals(SystemEvent::STATUS_ERROR, $this->evt->getStatus());
        self::assertMatchesRegularExpression('/Could not update the computed checksum for file/i', $this->evt->getLog());
    }

    public function testComparisonMd5sumFailure(): void
    {
        $this->evt->method('computeFRSMd5Sum')->willReturn(true);
        $this->evt->method('updateDB')->willReturn(true);
        $this->evt->method('compareMd5Checksums')->willReturn(false);

        $this->evt->method('sendNotificationMail')->willReturn(true);

        $this->evt->expects(self::once())->method('done');

        self::assertTrue($this->evt->process());
    }

    public function testComparisonMd5sumFailureFailsToSendAMail(): void
    {
        $this->evt->method('computeFRSMd5Sum')->willReturn(true);
        $this->evt->method('updateDB')->willReturn(true);
        $this->evt->method('compareMd5Checksums')->willReturn(false);

        $this->evt->method('sendNotificationMail')->willReturn(false);

        $this->evt->expects(self::never())->method('done');

        self::assertFalse($this->evt->process());

        // Check errors
        self::assertEquals(SystemEvent::STATUS_ERROR, $this->evt->getStatus());
        self::assertMatchesRegularExpression('/Could not send mail to inform user that comparing md5sum failed/i', $this->evt->getLog());
    }
}
