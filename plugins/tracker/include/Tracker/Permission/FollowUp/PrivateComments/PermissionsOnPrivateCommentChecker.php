<?php
/**
 *  Copyright (c) Maximaster, 2020. All rights reserved
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Tracker\Permission\FollowUp\PrivateComments;

use JetBrains\PhpStorm\Pure;
use PFUser;
use Tracker;
use Tuleap\Tracker\Permission\FollowUp\PrivateComments\TrackerPrivateCommentsDao;

class PermissionsOnPrivateCommentChecker
{
    /** @var $instance null */
    private static $instance = null;

    /** @var $private_comments_dao null|TrackerPrivateCommentsDao */
    private $private_comments_dao = null;

    /** @var $hashPrivateAccessGroups array */
    private $hashPrivateAccessGroups = [];

    private function __construct() {}

    private function __clone() {}

    private function __wakeup() {}

    public static function getInstance()
    {
        if (self::$instance === null) {
            return self::$instance = new static();
        } else {
            return self::$instance;
        }
    }

    public function checkPermission(PFUser $user, Tracker $tracker): bool
    {
        $user_ugroups = $user->getUgroups($tracker->getProject()->getID(), []);
        $private_comments_groups = $this->getPrivateCommentsGroups($tracker);

        if (!count($private_comments_groups) > 0) {
            return false;
        }

        if ($user->isAdmin($tracker->getProject()->getID())) {
            return true;
        }

        return count(array_intersect($user_ugroups, $private_comments_groups)) > 0;
    }

    private function getPrivateCommentsGroups(Tracker $tracker): array
    {
        if ($hashGroup = $this->getHashGroup()) {
            return $hashGroup;
        }

        if (!$this->private_comments_dao instanceof TrackerPrivateCommentsDao) {
            $this->private_comments_dao = $this->getTrackerPrivateCommentsDao();
        }

        $test = $this->private_comments_dao->getAccessUgroupsByTrackerId($tracker->getId());

        $private_comments_groups = array_column(
            $this->private_comments_dao->getAccessUgroupsByTrackerId($tracker->getId()),
            'ugroup_id'
        );

        $this->setHashGroup($private_comments_groups);

        return $private_comments_groups;
    }

    private function getHashGroup(): array
    {
        return $this->hashPrivateAccessGroups;
    }

    private function setHashGroup($privateCommentsGroups): void
    {
        $this->hashPrivateAccessGroups = $privateCommentsGroups;
    }

    private function getTrackerPrivateCommentsDao(): TrackerPrivateCommentsDao
    {
        return new TrackerPrivateCommentsDao();
    }
}
