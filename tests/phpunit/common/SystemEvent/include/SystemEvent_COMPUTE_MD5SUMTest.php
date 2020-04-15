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
class SystemEvent_COMPUTE_MD5SUMTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\Mock|SystemEvent_COMPUTE_MD5SUM
     */
    private $evt;
    /**
     * @var BaseLanguage|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $language;

    protected function setUp(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $this->evt = Mockery::mock(
            SystemEvent_COMPUTE_MD5SUM::class,
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


        $file = Mockery::spy(\FRSFile::class);
        $file->shouldReceive('getFileLocation')->andReturns('/var/lib/codendi/ftp/codendi/project_1/p2952_r10819/test.dump');
        $file->shouldReceive('getUserID')->andReturns(142);

        $file_factory = Mockery::mock(FRSFileFactory::class);
        $file_factory->shouldReceive('getFRSFileFromDB')->with('100012')->andReturn($file);

        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive(
            [
                'getEmail'  => 'mickey@codendi.org',
                'getLocale' => 'fr_FR',
            ]
        );

        $this->language = Mockery::mock(BaseLanguage::class);

        $base_language_factory = Mockery::mock(BaseLanguageFactory::class);
        $base_language_factory
            ->shouldReceive('getBaseLanguage')
            ->with('fr_FR')
            ->once()
            ->andReturn($this->language);

        $this->evt->shouldReceive(
            [
                'getFileFactory' => $file_factory,
                'getBaseLanguageFactory' => $base_language_factory,
                'getUser' => $user,
            ]
        );
    }

    /**
     * Compute md5sum
     */
    public function testComputeMd5sumSucceed(): void
    {
        $this->evt->shouldReceive('computeFRSMd5Sum')->andReturns('d41d8cd98f00b204e9800998ecf8427e');
        $this->evt->shouldReceive('updateDB')->andReturns(true);

        //Checksum comparison
        $this->evt->shouldReceive('compareMd5Checksums')->andReturns(true);
        // Expect everything went OK
        $this->evt->shouldReceive('sendNotificationMail')->andReturns(false);
        $this->evt->shouldReceive('done')->once();

        // Launch the event
        $this->assertTrue($this->evt->process());
    }

    public function testComputeMd5sumFailure(): void
    {
        $this->evt->shouldReceive('computeFRSMd5Sum')->andReturns(false);

        $this->language
            ->shouldReceive('getText')
            ->once()
            ->andReturn("Dear Files service user,\n\n An error occurs while trying to compute md5sum in your uploaded file $1.\nPlease try to upload it again.");

        $this->evt->shouldReceive('sendNotificationMail')->andReturns(true);

        $this->evt->shouldReceive('done')->never();

        $this->assertFalse($this->evt->process());

        // Check errors
        $this->assertEquals(SystemEvent::STATUS_ERROR, $this->evt->getStatus());
        $this->assertEquals('Computing md5sum failed', $this->evt->getLog());
    }

    public function testComputeMd5sumUpdateDBFailure(): void
    {
        $this->evt->shouldReceive('computeFRSMd5Sum')->andReturns('d41d8cd98f00b204e9800998ecf8427e');

        // DB
        $this->evt->shouldReceive('updateDB')->andReturns(false);

        $this->evt->shouldReceive('done')->never();
        $this->assertFalse($this->evt->process());

        // Check errors
        $this->assertEquals(SystemEvent::STATUS_ERROR, $this->evt->getStatus());
        $this->assertMatchesRegularExpression('/Could not update the computed checksum for file/i', $this->evt->getLog());
    }

    public function testComparisonMd5sumFailure(): void
    {
        $this->evt->shouldReceive('computeFRSMd5Sum')->andReturns(true);
        $this->evt->shouldReceive('updateDB')->andReturns(true);
        $this->evt->shouldReceive('compareMd5Checksums')->andReturns(false);

        $this->language
            ->shouldReceive('getText')
            ->once()
            ->andReturn("Dear Files service user, \n\nThe entered reference md5sum for the file $1 differs from the computed one which equals = $2.\n Note that an error message will be shown each time you display the release content in the web interface. \nIf you consider that the upload has been well done, you can modify the value in the md5sum field by putting the right value.");

        $this->evt->shouldReceive('sendNotificationMail')->andReturns(true);

        $this->evt->shouldReceive('done')->once();

        $this->assertTrue($this->evt->process());
    }

    public function testComparisonMd5sumFailureFailsToSendAMail(): void
    {
        $this->evt->shouldReceive('computeFRSMd5Sum')->andReturns(true);
        $this->evt->shouldReceive('updateDB')->andReturns(true);
        $this->evt->shouldReceive('compareMd5Checksums')->andReturns(false);

        $this->language
            ->shouldReceive('getText')
            ->once()
            ->andReturn("Dear Files service user, \n\nThe entered reference md5sum for the file $1 differs from the computed one which equals = $2.\n Note that an error message will be shown each time you display the release content in the web interface. \nIf you consider that the upload has been well done, you can modify the value in the md5sum field by putting the right value.");

        $this->evt->shouldReceive('sendNotificationMail')->andReturns(false);

        $this->evt->shouldReceive('done')->never();

        $this->assertFalse($this->evt->process());

        // Check errors
        $this->assertEquals(SystemEvent::STATUS_ERROR, $this->evt->getStatus());
        $this->assertMatchesRegularExpression('/Could not send mail to inform user that comparing md5sum failed/i', $this->evt->getLog());
    }
}
