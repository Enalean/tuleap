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
require_once 'GitExec.class.php';

Mock::generate('GitExec');
Mock::generate('ChangeDetector');
class GitTagFinderTest extends TuleapTestCase
{

    public function itFindsTheGreatestVersionNumberFromTheTags()
    {
        $gitExec = new MockGitExec();
        $checkReleaseGit = new LastReleaseFinder($gitExec);
        $this->assertEqual('4.01.0', $checkReleaseGit->maxVersion(array('4.0.2', '4.01.0')));
        $this->assertEqual('4.10', $checkReleaseGit->maxVersion(array('4.10', '4.9')));
    }

    public function itListsAllTags()
    {
         $version_list = array(
            'cef75eb766883a62700306de0e57a14b54aa72ec	refs/tags/4.0.2',
            'e0f6385781c8456e3b920284734786c5af2b7f12	refs/tags/4.01.0',
            'e0f6385781c8456e3b920284734786c5af2b7f12	refs/tags/4.1',
            'e0f6385781c8456e3b920284734786c5af2b7f12	refs/tags/4.9',
            'e0f6385781c8456e3b920284734786c5af2b7f12	refs/tags/4.10');
         $gitExec = new MockGitExec();
         $gitExec->setReturnValue('lsRemote', $version_list, array('origin'));
         $git_tag_finder = new LastReleaseFinder($gitExec);
         $this->assertEqual(array('4.0.2', '4.01.0', '4.1', '4.9', '4.10'), $git_tag_finder->getReleaseList('origin'));
    }

    public function itListsOnlyTagsThatAreNumeric()
    {
         $version_list = array(
            'cef75eb766883a62700306de0e57a14b54aa72ec	refs/branches/4.0.2',
            'e0f6385781c8456e3b920284734786c5af2b7f12	refs/tags/textualTag',
            'e0f6385781c8456e3b920284734786c5af2b7f12	refs/tags/437_numericalbeginning',
            'e0f6385781c8456e3b920284734786c5af2b7f12	refs/tags/4.1');
         $gitExec = new MockGitExec();
         $gitExec->setReturnValue('lsRemote', $version_list, array('origin'));
         $git_tag_finder = new LastReleaseFinder($gitExec);
         $this->assertEqual(array('4.1'), $git_tag_finder->getReleaseList('origin'));
    }

    public function itGetsTheMaxVersionDirectlyFromTheRemote()
    {
         $version_list = array(
            'cef75eb766883a62700306de0e57a14b54aa72ec	refs/tags/4.0.2',
            'e0f6385781c8456e3b920284734786c5af2b7f12	refs/tags/4.01.0',
            'e0f6385781c8456e3b920284734786c5af2b7f12	refs/tags/4.1',
            'e0f6385781c8456e3b920284734786c5af2b7f12	refs/tags/4.9',
            'e0f6385781c8456e3b920284734786c5af2b7f12	refs/tags/4.10');
         $gitExec = new MockGitExec();
         $gitExec->setReturnValue('lsRemote', $version_list, array('origin'));
         $git_tag_finder = new LastReleaseFinder($gitExec);
         $this->assertEqual('4.10', $git_tag_finder->retrieveFrom('origin'));
    }
}

class GitChangeDetectorTest extends TuleapTestCase
{

    public function itFindsOnlyChangedPaths()
    {
        $revision = 'refs/tags/4.0.29';
        $gitExec = new MockGitExec();
        $gitExec->setReturnValue('hasChangedSince', true, array('plugins/docman', $revision));
        $gitExec->setReturnValue('hasChangedSince', false, array('plugins/tracker', $revision));

        $candidate_paths = array('plugins/docman', 'plugins/tracker');

        $release_checker = new ChangeDetector($gitExec, $candidate_paths);
        $this->assertEqual(array('plugins/docman'), $release_checker->findPathsThatChangedSince($revision));
    }
}

class NonIncrementedPathFinderTest extends TuleapTestCase
{

    public function itFiltersPathsThatHaveBeenIncremented()
    {

        $last_release_tag = 'refs/tags/4.0.29';
        $current_version  = 'HEAD';
        $changed_paths = array('src/www/soap', 'plugins/mailman');
        $expected_paths = array('plugins/mailman');
        $gitExec = new MockGitExec();
        $gitExec->setReturnValue('fileContent', '1.1', array('src/www/soap/VERSION', $last_release_tag));
        $gitExec->setReturnValue('fileContent', '1.2', array('src/www/soap/VERSION', $current_version));
        $gitExec->setReturnValue('fileContent', '2.3', array('plugins/mailman/VERSION', $last_release_tag));
        $gitExec->setReturnValue('fileContent', '2.3', array('plugins/mailman/VERSION', $current_version));

        $change_detector = new MockChangeDetector();
        $change_detector->setReturnValue('findPathsThatChangedSince', $changed_paths);

        $version_increment_filter = new NonIncrementedPathFinder($gitExec, $last_release_tag, $change_detector);
        $actual_non_incremented_paths = $version_increment_filter->pathsThatWereNotProperlyIncremented($current_version);
        $this->assertEqual($expected_paths, $actual_non_incremented_paths);
    }
}
