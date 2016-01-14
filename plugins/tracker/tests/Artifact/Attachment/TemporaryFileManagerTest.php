<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

require_once __DIR__ .'/../../bootstrap.php';

class TemporaryFileManager_BaseTest extends TuleapTestCase {

    protected $file_manager;
    protected $cache_dir;

    public function setUp() {
        parent::setUp();
        ForgeConfig::store();

        $this->cache_dir = trim(`mktemp -d -p /var/tmp cache_dir_XXXXXX`);
        ForgeConfig::set('codendi_cache_dir', $this->cache_dir);

        $user = aUser()->withId(101)->build();
        $dao  = mock('Tracker_Artifact_Attachment_TemporaryFileManagerDao');
        stub($dao)->create()->returns(1);

        $file_info_factory = mock('Tracker_FileInfoFactory');

        $this->file_manager = new Tracker_Artifact_Attachment_TemporaryFileManager($user, $dao, $file_info_factory);
    }

    public function tearDown() {
        exec('rm -rf '. escapeshellarg($this->cache_dir));
        ForgeConfig::restore();
        parent::tearDown();
    }
}

class TemporaryFileManager_getDiskUsageTest extends TemporaryFileManager_BaseTest {

    public function itReturns0WhenNoFiles() {
        $this->assertEqual(0, $this->file_manager->getDiskUsage());
    }

    public function itReturnsTheSizeOfTheOnlyFile() {
        file_put_contents($this->cache_dir .'/rest_attachement_temp_101_mona_lisa.png', 'Content');

        $this->assertEqual(7, $this->file_manager->getDiskUsage());
    }

    public function itSumsUpAllTheFiles() {
        file_put_contents($this->cache_dir .'/rest_attachement_temp_101_mona_lisa.png', 'Content');
        file_put_contents($this->cache_dir .'/rest_attachement_temp_101_liza_monet.png', 'Another content');

        $this->assertEqual(22, $this->file_manager->getDiskUsage());
    }

    public function itSumsOnlyCurrentUserFiles() {
        file_put_contents($this->cache_dir .'/rest_attachement_temp_101_mona_lisa.png', 'Content');
        file_put_contents($this->cache_dir .'/rest_attachement_temp_101_liza_monet.png', 'Another content');
        file_put_contents($this->cache_dir .'/rest_attachement_temp_102_hannibal_lecteur.png', 'Whatever');

        $this->assertEqual(22, $this->file_manager->getDiskUsage());

    }
}

class TemporaryFileManager_saveTest extends TemporaryFileManager_BaseTest {

    public function setUp() {
        parent::setUp();
        ForgeConfig::set('sys_max_size_upload', 10);
    }

    public function itCanSaveATemporaryFilesIfQuotaIsNotExceeded() {
        file_put_contents($this->cache_dir .'/rest_attachement_temp_101_mona_lisa.png', 'Content');

        $temporary = $this->file_manager->save('jette_lit.png', 'Mugshot', 'image/png');

        $this->assertEqual('jette_lit.png', $temporary->getName());
    }

    public function itCanSaveATemporaryFilesIfQuotaIsExceededBySomeoneElse() {
        file_put_contents($this->cache_dir .'/rest_attachement_temp_102_mona_lisa.png', 'Content that exceed quota');

        $temporary = $this->file_manager->save('jette_lit.png', 'Mugshot', 'image/png');

        $this->assertEqual('jette_lit.png', $temporary->getName());
    }

    public function itCannotSaveATemporaryFilesIfQuotaIsExceeded() {
        file_put_contents($this->cache_dir .'/rest_attachement_temp_101_mona_lisa.png', 'Content that exceed quota');

        $this->expectException('Tuleap\Tracker\Artifact\Attachment\QuotaExceededException');

        $this->file_manager->save('jette_lit.png', 'Mugshot', 'image/png');
    }
}

class TemporaryFileManager_appendChunkTest extends TemporaryFileManager_BaseTest {

    private $empty_file;
    private $wrong_path_file;

    public function setUp() {
        parent::setUp();
        ForgeConfig::set('sys_max_size_upload', 10);
        $this->empty_file = new Tracker_Artifact_Attachment_TemporaryFile(
            1,
            'jette_lit.png',
            'random_tmpname',
            'Mugshot',
            0,
            0,
            101,
            0,
            'image/png'
        );
        touch($this->cache_dir .'/rest_attachement_temp_101_'. $this->empty_file->getTemporaryName());

        $this->wrong_path_file = new Tracker_Artifact_Attachment_TemporaryFile(
            1,
            'jette_lit.png',
            'wrong_path',
            'Mugshot',
            0,
            0,
            101,
            0,
            'image/png'
        );
    }

    public function itThrowsExceptionIfOffsetIsNotValid() {
        $this->expectException('Tracker_Artifact_Attachment_InvalidOffsetException');

        $this->file_manager->appendChunk(base64_encode('le content'), $this->empty_file, 2);
    }

    public function itThrowsExceptionIfFileDoesNotExist() {
        $this->expectException('Tracker_Artifact_Attachment_InvalidPathException');

        $this->file_manager->appendChunk(base64_encode('le content'), $this->wrong_path_file, 1);
    }

    public function itWritesChunkOnTheDisk() {
        $filepath = $this->cache_dir .'/rest_attachement_temp_101_'. $this->empty_file->getTemporaryName();

        $this->file_manager->appendChunk(base64_encode('le content'), $this->empty_file, 1);

        $this->assertEqual('le content', file_get_contents($filepath));
    }

    public function itThrowsExceptionIfChunkIsTooBig() {
        $filepath = $this->cache_dir .'/rest_attachement_temp_101_'. $this->empty_file->getTemporaryName();
        $this->expectException('Tuleap\Tracker\Artifact\Attachment\QuotaExceededException');

        $this->file_manager->appendChunk(base64_encode('le too big content'), $this->empty_file, 1);

        $this->assertEqual('', file_get_contents($filepath));
    }
}
