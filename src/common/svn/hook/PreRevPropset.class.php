<?php
/**
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

/**
 * I'm responsible of handling what happens in pre-revprop-change subversion hook
 */
class SVN_Hook_PreRevPropset extends SVN_Hook
{

    /**
     * Check if the property can be modified
     *
     * Be careful due to a svn bug, this method will be call twice by svn server <= 1.7
     * The first time with the new commit_message as expected
     * The second time with the OLD commit message (oh yeah baby!)
     * http://subversion.tigris.org/issues/show_bug.cgi?id=3085
     *
     * @param String $repository
     * @param String $action
     * @param String $propname
     * @param String $commit_message
     */
    public function assertCanBeModified($repository, $action, $propname, $commit_message)
    {
        $this->assertPropsetIsOnLog($action, $propname);
        $project = $this->getProjectFromRepositoryPath($repository);
        $this->assertCommitMessageCanBeModified($project);
        $this->message_validator->assertCommitMessageIsValid($project, $commit_message);
    }

    private function assertPropsetIsOnLog($action, $propname)
    {
        if (! ($action == 'M' && $propname == 'svn:log')) {
            throw new Exception('Cannot modify anything but svn:log');
        }
    }

    private function assertCommitMessageCanBeModified(Project $project)
    {
        if (! $project->canChangeSVNLog()) {
            throw new Exception('Project forbid to change log messages');
        }
    }
}
