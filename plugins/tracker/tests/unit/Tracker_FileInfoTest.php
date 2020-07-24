<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_FileInfoTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\ForgeConfigSandbox;

    private $fixture_data_dir;
    private $working_directory;
    /** @var Tracker_FormElement_Field_File */
    private $field;
    /** @var Tracker_FileInfo */
    private $file_info_1;
    /** @var Tracker_FileInfo */
    private $file_info_2;
    /**
     * @var string
     */
    private $thumbnails_dir;

    protected function setUp(): void
    {
        $field_id = 123;
        $this->fixture_data_dir  = __DIR__ . '/_fixtures/attachments';
        $this->working_directory = \org\bovigo\vfs\vfsStream::setup()->url();
        $this->thumbnails_dir    = $this->working_directory . '/thumbnails';
        mkdir($this->thumbnails_dir);
        $this->field = \Mockery::spy(\Tracker_FormElement_Field_File::class);
        $this->field->shouldReceive('getId')->andReturns($field_id);
        $this->field->shouldReceive('getRootPath')->andReturns($this->working_directory);

        $id           = 1;
        $submitted_by = 103;
        $description  = 'Screenshot of the issue';
        $filename     = 'screenshot.png';
        $filesize     = 285078;
        $filetype     = 'image/png';
        $this->file_info_1 = new Tracker_FileInfo($id, $this->field, $submitted_by, $description, $filename, $filesize, $filetype);

        $filetype     = 'image/tiff';
        $this->file_info_2 = new Tracker_FileInfo($id, $this->field, $submitted_by, $description, $filename, $filesize, $filetype);
    }

    protected function tearDown(): void
    {
        Backend::clearInstances();
    }

    public function testProperties(): void
    {
        $this->assertEquals('Screenshot of the issue', $this->file_info_1->getDescription());
        $this->assertEquals(103, $this->file_info_1->getSubmittedBy());
        $this->assertEquals('screenshot.png', $this->file_info_1->getFilename());
        $this->assertEquals(285078, $this->file_info_1->getFilesize());
        $this->assertEquals('image/png', $this->file_info_1->getFiletype());
        $this->assertEquals(1, $this->file_info_1->getId());
    }

    public function testGetPath(): void
    {
        $this->assertEquals($this->working_directory . '/1', $this->file_info_1->getPath());
        $this->assertEquals($this->working_directory . '/thumbnails/1', $this->file_info_1->getThumbnailPath());
        $this->assertNull($this->file_info_2->getThumbnailPath(), "A file that is not an image doesn't have any thumbnail (for now)");
    }

    /**
     * @dataProvider dataProviderIsImage
     */
    public function testIsImage(string $filetype, bool $is_image): void
    {
        $fi = new Tracker_FileInfo(1, Mockery::mock(Tracker_FormElement_Field::class), 102, 'description', 'image', 10, $filetype);

        $this->assertEquals($is_image, $fi->isImage());
    }

    public function dataProviderIsImage(): array
    {
        return [
            ['image/png', true],
            ['image/gif', true],
            ['image/jpg', true],
            ['image/jpeg', true],
            'image/tiff is not a supported image type' => ['image/tiff', false],
            ['text/plain', false],
            ['text/gif', false],
        ];
    }

    /**
     * @dataProvider dataProviderHumanReadableFilesize
     */
    public function testHumanReadableFilesize(int $size, string $expected_human_readable_filesize): void
    {
        $f  = new Tracker_FileInfo(1, Mockery::mock(Tracker_FormElement_Field::class), 102, 'description', 'name', $size, 'text/plain');
        $this->assertEquals($expected_human_readable_filesize, $f->getHumanReadableFilesize());
    }

    public function dataProviderHumanReadableFilesize(): array
    {
        return [
            [0, '0 B'],
            [100, '100 B'],
            [1000, '1000 B'],
            [1024, '1 kB'],
            [10240, '10 kB'],
            [1000000, '977 kB'],
            [1024 * 100, '100 kB'],
            [1024 * 1000, '1000 kB'],
            [1024 * 1000 * 10, '10 MB'],
            [1024 * 1000 * 100, '98 MB'],
            [1024 * 1000 * 1000, '977 MB'],
        ];
    }

    public function testItCreatesThumbnailForPng(): void
    {
        $backend = \Mockery::mock(Backend::class);
        Backend::setInstance(Backend::BACKEND, $backend);
        $backend->shouldReceive('changeOwnerGroupMode');

        copy($this->fixture_data_dir . '/logo.png', $this->working_directory . '/66');

        $file_info_1 = new Tracker_FileInfo(66, $this->field, 0, '', '', '', 'image/png');
        $this->assertFileDoesNotExist($file_info_1->getThumbnailPath());
        $file_info_1->postUploadActions();

        $this->assertFileExists($file_info_1->getThumbnailPath());
        $this->assertEquals(
            [
                112,
                112,
                IMAGETYPE_PNG,
                'width="112" height="112"',
                'bits' => 8,
                'mime' => 'image/png'
            ],
            getimagesize($file_info_1->getThumbnailPath())
        );
    }

    public function testItCreatesThumbnailForGif(): void
    {
        $backend = \Mockery::mock(Backend::class);
        Backend::setInstance(Backend::BACKEND, $backend);
        $backend->shouldReceive('changeOwnerGroupMode');

        copy($this->fixture_data_dir . '/logo.gif', $this->working_directory . '/111');

        $file_info_1 = new Tracker_FileInfo(111, $this->field, 0, '', '', '', 'image/gif');
        $this->assertFileDoesNotExist($file_info_1->getThumbnailPath());
        $file_info_1->postUploadActions();

        $this->assertFileExists($file_info_1->getThumbnailPath());
        $this->assertEquals(
            [
                112,
                112,
                IMAGETYPE_GIF,
                'width="112" height="112"',
                'bits' => 6,
                'channels' => 3,
                'mime' => 'image/gif'
            ],
            getimagesize($file_info_1->getThumbnailPath())
        );
    }

    public function testItCreatesThumbnailForJpeg(): void
    {
        $backend = \Mockery::mock(Backend::class);
        Backend::setInstance(Backend::BACKEND, $backend);
        $backend->shouldReceive('changeOwnerGroupMode');

        copy($this->fixture_data_dir . '/logo.jpg', $this->working_directory . '/421');

        $file_info_1 = new Tracker_FileInfo(421, $this->field, 0, '', '', '', 'image/jpg');
        $this->assertFileDoesNotExist($file_info_1->getThumbnailPath());
        $file_info_1->postUploadActions();

        $this->assertFileExists($file_info_1->getThumbnailPath());
        $this->assertEquals(
            [
                112,
                112,
                IMAGETYPE_JPEG,
                'width="112" height="112"',
                'bits' => 8,
                'channels' => 3,
                'mime' => 'image/jpeg'
            ],
            getimagesize($file_info_1->getThumbnailPath())
        );
    }

    public function testItEnsuresFilesIsOwnedByHttpUser(): void
    {
        $backend = \Mockery::mock(Backend::class);
        Backend::setInstance(Backend::BACKEND, $backend);

        copy($this->fixture_data_dir . '/logo.jpg', $this->working_directory . '/421');

        $file_info_1 = new Tracker_FileInfo(421, $this->field, 0, '', '', '', 'image/jpg');
        ForgeConfig::set('sys_http_user', 'user');

        $backend->shouldReceive('changeOwnerGroupMode')->with($this->working_directory . '/421', 'user', 'user', 0644)->once();

        $file_info_1->postUploadActions();
    }
}
