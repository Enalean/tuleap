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
class MD5SumComparisonTest extends TuleapTestCase {

    function __construct($name = 'MD5SumComparisonTest test') {
        parent::__construct($name);
    }

    public function setUp()
    {
        parent::setUp();
        $this->fixDir    = dirname(__FILE__). '/_fixtures/big_dir';
        $this->readPath  = $this->fixDir.'/file_2.5GB';

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
        parent::tearDown();
    }

    function testMd5sumDelay() {
        $startTime = microtime(true);
        $md5PhpCompute = md5_file($this->readPath);
        $endTime = microtime(true);
        $delay = $endTime - $startTime;
        echo "Le delay to compute the md5sum is ".$delay;
        $md5SystemCompute = trim(`md5sum $this->readPath| awk '{ print $1 }'`);
        if ($md5SystemCompute) {
            $this->assertIdentical($md5SystemCompute , $md5PhpCompute);
            echo "\nLe md5sum computed is ".$md5SystemCompute."\n";
        } else {
            trigger_error('Can not compute md5sum on file');
            
        }
    }
}
