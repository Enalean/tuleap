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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class SystemEvent_COMPUTE_MD5SUM_Test extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['sys_name'] = 'Codendi';
        $GLOBALS['sys_noreply'] = '"Codendi" <noreply@codendi.org>';
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['sys_name']);
        unset($GLOBALS['sys_noreply']);
        parent::tearDown();
    }

    /**
     * Compute md5sum
     */
    public function testComputeMd5sumSucceed(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = \Mockery::mock(
            \SystemEvent_COMPUTE_MD5SUM::class,
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
                ''
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // The file
        $fileFactory = \Mockery::mock(FRSFileFactory::class);
        $file = \Mockery::spy(\FRSFile::class);
        $evt->shouldReceive('getFileFactory')->andReturns($fileFactory);
        $fileFactory->shouldReceive('getFRSFileFromDB')->with('100012')->andReturn($file);
        $file->shouldReceive('getFileLocation')->andReturns('/var/lib/codendi/ftp/codendi/project_1/p2952_r10819/test.dump');

        $evt->shouldReceive('computeFRSMd5Sum')->andReturns('d41d8cd98f00b204e9800998ecf8427e');

        // DB
        $evt->shouldReceive('updateDB')->andReturns(true);

        //Checksum comparison
        $evt->shouldReceive('compareMd5Checksums')->andReturns(true);
        // Expect everything went OK
        $evt->shouldReceive('done')->once();

        // Launch the event
        $this->assertTrue($evt->process());
    }

    public function testComputeMd5sumFailure(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = \Mockery::mock(
            \SystemEvent_COMPUTE_MD5SUM::class,
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
                ''
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // The file
        $fileFactory = \Mockery::mock(FRSFileFactory::class);
        $file = \Mockery::spy(\FRSFile::class);
        $evt->shouldReceive('getFileFactory')->andReturns($fileFactory);
        $fileFactory->shouldReceive('getFRSFileFromDB')->with('100012')->andReturn($file);
        $file->shouldReceive('getFileLocation')->andReturns('/var/lib/codendi/ftp/codendi/project_1/p2952_r10819/test.dump');
        $file->shouldReceive('getUserID')->andReturns(142);

        $evt->shouldReceive('computeFRSMd5Sum')->andReturns(false);

        // The user
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getEmail')->andReturns('mickey@codendi.org');
        $evt->shouldReceive('getUser')->andReturns($user);
        $evt->shouldReceive('sendNotificationMail')->andReturns(false);

        $evt->shouldReceive('done')->never();

        $this->assertFalse($evt->process());

        // Check errors
        $this->assertEquals(SystemEvent::STATUS_ERROR, $evt->getStatus());
        $this->assertRegExp('/Could not send mail to inform user that computing md5sum failed/i', $evt->getLog());
    }

    public function testComputeMd5sumUpdateDBFailure(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = \Mockery::mock(
            \SystemEvent_COMPUTE_MD5SUM::class,
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
                ''
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // The file
        $fileFactory = \Mockery::mock(FRSFileFactory::class);
        $file = \Mockery::spy(\FRSFile::class);
        $evt->shouldReceive('getFileFactory')->andReturns($fileFactory);
        $fileFactory->shouldReceive('getFRSFileFromDB')->andReturn($file);
        $file->shouldReceive('getFileLocation')->andReturns('/var/lib/codendi/ftp/codendi/project_1/p1827_r6573/webkit-1.0.tar.gz');

        $evt->shouldReceive('computeFRSMd5Sum')->andReturns('d41d8cd98f00b204e9800998ecf8427e');

        // DB
        $evt->shouldReceive('updateDB')->andReturns(false);

        $evt->shouldReceive('done')->never();
        $this->assertFalse($evt->process());

        // Check errors
        $this->assertEquals(SystemEvent::STATUS_ERROR, $evt->getStatus());
        $this->assertRegExp('/Could not update the computed checksum for file/i', $evt->getLog());
    }

    public function testComparisonMd5sumFailure(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = \Mockery::mock(
            \SystemEvent_COMPUTE_MD5SUM::class,
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
                ''
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // The file
        $fileFactory = \Mockery::mock(FRSFileFactory::class);
        $file = \Mockery::spy(\FRSFile::class);
        $evt->shouldReceive('getFileFactory')->andReturns($fileFactory);
        $fileFactory->shouldReceive('getFRSFileFromDB')->with('100012')->andReturn($file);
        $file->shouldReceive('getFileLocation')->andReturns('/var/lib/codendi/ftp/codendi/project_1/p2952_r10819/test.dump');
        $file->shouldReceive('getUserID')->andReturns(142);

        $evt->shouldReceive('computeFRSMd5Sum')->andReturns(true);
        $evt->shouldReceive('updateDB')->andReturns(true);
        $evt->shouldReceive('compareMd5Checksums')->andReturns(false);

        // The user
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getEmail')->andReturns('mickey@codendi.org');
        $evt->shouldReceive('getUser')->andReturns($user);
        $evt->shouldReceive('sendNotificationMail')->andReturns(false);

        $evt->shouldReceive('done')->never();

        $this->assertFalse($evt->process());

        // Check errors
        $this->assertEquals(SystemEvent::STATUS_ERROR, $evt->getStatus());
        $this->assertRegExp('/Could not send mail to inform user that comparing md5sum failed/i', $evt->getLog());
    }
}
