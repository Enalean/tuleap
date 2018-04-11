<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

abstract class TuleapTestCase extends UnitTestCase
{
    /**
     * @var String Path to a directory where temporary things can be done
     */
    private $tmp_dir;

    protected function isTest($method)
    {
        if (strtolower(substr($method, 0, 2)) == 'it') {
            return ! is_a($this, strtolower($method));
        }
        return parent::isTest($method);
    }

    public function tearDown()
    {
        $this->removeTmpDir();

        // Include mocker assertions into SimpleTest results
        if ($container = \Mockery::getContainer()) {
            for ($i = 0; $i < $container->mockery_getExpectationCount(); $i++) {
                $this->pass();
            }
        }
        \Mockery::close();

        parent::tearDown();
    }

    /**
     * Creates a tmpDir and returns the path (dir automtically deleted in tearDown)
     */
    protected function getTmpDir() {
        if (!$this->tmp_dir) {
            clearstatcache();
            do {
                $this->tmp_dir = '/tmp/tuleap_tests_'.rand(0,10000);
            } while (file_exists($this->tmp_dir));
        }
        if (!is_dir($this->tmp_dir)) {
            mkdir($this->tmp_dir, 0700, true);
        }
        return $this->tmp_dir;
    }

    private function removeTmpDir() {
        if ($this->tmp_dir && file_exists($this->tmp_dir)) {
            $this->recurseDeleteInDir($this->tmp_dir);
            rmdir($this->tmp_dir);
        }
        clearstatcache();
    }


    /**
     * Recursive rm function.
     * see: http://us2.php.net/manual/en/function.rmdir.php#87385
     * Note: the function will empty everything in the given directory but won't remove the directory itself
     *
     * @param string $mypath Path to the directory
     *
     * @return void
     */
    protected function recurseDeleteInDir($mypath) {
        $mypath = rtrim($mypath, '/');
        $d      = opendir($mypath);
        if (! $d) {
            return;
        }
        while (($file = readdir($d)) !== false) {
            if ($file != "." && $file != "..") {

                $typepath = $mypath . "/" . $file ;

                if (is_file($typepath) || is_link($typepath)) {
                    unlink($typepath);
                } else {
                    $this->recurseDeleteInDir($typepath);
                    rmdir($typepath);
                }
            }
        }
        closedir($d);
    }
}
