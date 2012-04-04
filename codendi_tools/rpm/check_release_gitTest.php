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

require_once 'CheckReleaseGit.class.php';
class check_releqse_gitTest extends TuleapTestCase {
    public function itFindsTheGreatestVersionNumberFromTheTags() {
        $this->assertEqual(0, 0);
    }
    
    public function itListsAllTags() {
        $release_checker = new CheckReleaseGit();
         $version_list = $release_checker->getVersionList(
'cef75eb766883a62700306de0e57a14b54aa72ec	refs/tags/4.0.2\n
e0f6385781c8456e3b920284734786c5af2b7f12	refs/tags/4.01.0\n
e0f6385781c8456e3b920284734786c5af2b7f12	refs/tags/4.1\n
e0f6385781c8456e3b920284734786c5af2b7f12	refs/tags/4.9\n
e0f6385781c8456e3b920284734786c5af2b7f12	refs/tags/4.10');
        
        $this->assertEqual(array('4.0.2', '4.01.0', '4.1', '4.9', '4.10'), $version_list);
    }
    
}
?>
