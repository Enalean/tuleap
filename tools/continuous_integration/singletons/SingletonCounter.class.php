<?php

/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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


define("PROJECT_BASEDIR", dirname(__FILE__).'/../../..');
define("SINGLETON_COUNT_FILE", dirname(__FILE__).'/current_singleton_count.txt');

/**
 * Count singleton lookups in project.
 * Replace current amount in reference file SINGLETON_COUNT_FILE.
 * Provide current amount in reference file SINGLETON_COUNT_FILE.
 */
class SingletonCounter {

    public function countSingletonLookupsInProject() {
        $basedir                    = PROJECT_BASEDIR;
        $dirs                       = "$basedir/plugins $basedir/src $basedir/tools";
        $count_command              = "grep -rc --exclude='*~' '::instance()' $dirs| awk -F: '{n=n+$2} END { print n}'";
        $output                     = $this->getSystemOutput($count_command);
        return $output[0];
    }

    private function getSystemOutput($cmd) {
        $result;
        exec($cmd, $result);
        return $result;
    }
   
    public function replaceExpectedSingletonCountWithActualCount() {
        $this->replaceExpectedSingletonCountWith($this->countSingletonLookupsInProject());
    }

    public function replaceExpectedSingletonCountWith($count) {
        file_put_contents(SINGLETON_COUNT_FILE, $count.PHP_EOL);
    }

    public function expectedSingletonCount() {
        return trim(file_get_contents(SINGLETON_COUNT_FILE));
    }
    
    public function getSingletoncountFilename() {
        return SINGLETON_COUNT_FILE;
    }

}

?>
