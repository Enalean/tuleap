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

require_once 'common/svn/SVN_Hooks.class.php';

class SVN_Hook_PreRevPropset {

    /** @var SVN_Hooks */
    private $svn_hooks;

    /** @var ReferenceManager */
    private $reference_manager;

    public function __construct(SVN_Hooks $svn_hooks, ReferenceManager $reference_manager) {
        $this->svn_hooks         = $svn_hooks;
        $this->reference_manager = $reference_manager;
    }

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
    public function assertCanBeModified($repository, $action, $propname, $commit_message) {
        $this->propsetIsOnLog($action, $propname);
        $project = $this->svn_hooks->getProjectFromRepositoryPath($repository);
        $this->commitMessageCanBeModified($project);
        $this->commitMessageIsValid($project, $commit_message);
    }

    private function propsetIsOnLog($action, $propname) {
        if (! ($action == 'M' && $propname == 'svn:log')) {
            throw new Exception('Cannot modify anything but svn:log');
        }
    }

    private function commitMessageCanBeModified(Project $project) {
        if (! $project->canChangeSVNLog()) {
            throw new Exception('Project forbid to change log messages');
        }
    }

    private function commitMessageIsValid(Project $project, $commit_message) {
        if ($project->isSVNMandatoryRef()) {
            // Marvelous, extractCrossRef depends on globals group_id to find the group
            // when it's not explicit... yeah!
            $GLOBALS['group_id'] = $project->getID();
            if (! $this->reference_manager->stringContainsReferences($commit_message, $project)) {
                throw new Exception('Commit message must contains references');
            }
        }
    }
}


?>
