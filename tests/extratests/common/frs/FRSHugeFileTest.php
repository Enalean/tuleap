<?php
/*
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */
require_once('common/frs/FRSFile.class.php');

class FRSHugeFileTest extends TuleapTestCase {

    function __construct($name = 'FRSHugeFileTest test') {
        parent::__construct($name);
    }

    public function setUp()
    {
        parent::setUp();
        $this->fixDir    = dirname(__FILE__). '/_fixtures/big_dir';
        $this->readPath  = $this->fixDir.'/file_2.5GB';
        $this->writePath = $this->fixDir.'/file_2.5GB_copy';

        // Generate big file
        if (is_link($this->fixDir)) {
            $parentPath = realpath($this->fixDir);
        } else {
            $parentPath = $this->fixDir;
        }
        if (!is_dir($parentPath)) {
            mkdir($this->fixDir);
        }
        $cmd = '/bin/df --portability '.escapeshellarg($parentPath).' | tail -1 | awk \'{print $4}\'';
        $spaceLeft = `$cmd` ;
        if ($spaceLeft < 5200000) {
            trigger_error("No sufficient space to create ".$this->readPath.". Cannot test big files. Tip: link ".$this->fixDir." to a partition with more than 5GB available.", E_USER_WARNING);
        } else {
            $output      = null;
            $returnValue = null;
            exec('dd if=/dev/urandom of='. $this->readPath .' bs=1M count=2500', $output, $returnValue);
            if ($returnValue != 0) {
                trigger_error('dd failed, unable to generate the big file');
            }
        }
    }

    public function tearDown()
    {
        unlink(realpath($this->readPath));
        unlink(realpath($this->writePath));
        parent::tearDown();
    }

    function testWithBigFile() {
        $writeFile = fopen(PHP_BigFile::stream($this->writePath), 'wb');
        $this->assertTrue($writeFile);

        $file = new FRSFile();
        $file->file_location = $this->readPath;

        $fileSize  = PHP_BigFile::getSize($this->readPath);
        $chunkSize = 8*1024*1024;
        $nbChunks  = ceil($fileSize / $chunkSize);
        for ($i = 0; $i < $nbChunks; $i++) {
            $data    = $file->getContent($i * $chunkSize, $chunkSize);
            $written = fwrite($writeFile, $data);
            $this->assertEqual(strlen($data), $written);
        }
        $this->assertIdentical(md5_file($this->readPath), md5_file($this->writePath));
    }
}
