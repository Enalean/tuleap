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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * This class do the mapping between Tuleap And Mediawiki groups
 */

class MediawikiGroupsMapper {

    /** @var MediawikiDao */
    private $dao;

    public function __construct(MediawikiDao $dao) {
        $this->dao = $dao;
    }

    public function defineUserMediawikiGroups(PFUser $user, Group $project) {
        $mediawiki_groups            = array();
        $mediawiki_groups['removed'] = $this->getUnconsistantMediawikiGroups($user, $project);

        if ($user->isMember($project->getID(), 'A')) {
            $mediawiki_groups['added'][] = 'bureaucrat';
            $mediawiki_groups['added'][] = 'sysop';

        } else if (($project->isPublic() && ! $user->isAnonymous()) || $user->isMember($project->getID())) {
            $mediawiki_groups['added'][] = 'user';
            $mediawiki_groups['added'][] = 'autoconfirmed';
            $mediawiki_groups['added'][] = 'emailconfirmed';

        } else {
            $mediawiki_groups['added'][] = '*';
        }

        return $mediawiki_groups;
    }

    private function getUnconsistantMediawikiGroups(PFUser $user, Group $project) {
        $unconsistant_mediwiki_groups = array();
        $mediawiki_explicit_groups    = $this->dao->getMediawikiGroupsForUser($user, $project);

        if ($mediawiki_explicit_groups) {
            foreach ($mediawiki_explicit_groups as $current_mediawiki_group) {
                if (preg_match('/^ForgeRole*/', $current_mediawiki_group)) {
                    $unconsistant_mediwiki_groups[] = $current_mediawiki_group;
                }
            }
        }

        return $unconsistant_mediwiki_groups;
    }

}