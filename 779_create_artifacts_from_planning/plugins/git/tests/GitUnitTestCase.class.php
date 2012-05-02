<?php
/*
 * Copyright (c) Xerox, 2009. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2009. Xerox Codendi Team.
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
/**
 * Description of GitUnitTestCaseclass
 *
 * @author gstorchi
 */
class GitUnitTestCase extends UnitTestCase {
    //put your code here
    protected $rootPath;
    
    public function setUp() {
        $this->rootPath = '/tmp/'.basename(__FILE__);        
    }

    public function tearDown() {
        
    }

    protected function createRepo($path) {
        chdir($this->rootPath);
        mkdir($path, 0777, true);
        chdir($path);
        $rcode = 0;
        system('git --bare init', $rcode);
        if ( $rcode != 0 ) {
           return false;
        }
        return true;        
    }


    protected function createTestBareRepo($name) {
        chdir($this->rootPath);
        $rcode = 0;
        system('mkdir repo'.$name.' clone'.$name.';cd repo'.$name.';git --bare init', $rcode);
        if ( $rcode != 0 ) {
            return false;
        }
        chdir('clone'.$name);
        return true;
    }

    protected function createTestRepo($name)  {
        chdir($this->rootPath);
        mkdir('repo'.$name);
        system('git clone '.$this->remoteUrlToClone);
        chdir('repo'.$name);
        return $this->rootPath.DIRECTORY_SEPARATOR.'repo'.$name;
    }

    protected function createBranch($branchName) {
        system('git checkout -b '.$branchName.' origin/master' );
    }

    protected function removeTestDir() {
        $this->rootPath = '/tmp/'.basename(__FILE__);
        if ( file_exists($this->rootPath) ) {
            $rcode = 0;
            $msg = system('rm -fr '.$this->rootPath, $rcode);
            if ( $rcode != 0 ) {
                $this->fail($msg);
            }
        }
    }
}

?>
