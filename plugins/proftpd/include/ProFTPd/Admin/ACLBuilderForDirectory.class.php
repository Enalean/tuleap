<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\ProFTPd\Admin;

/**
 * For directories we need to set both default ACL (d:...) and effective ones.
 * Default acl are here to be inherited by newly created files and directories
 */
class ACLBuilderForDirectory extends ACLBuilder
{

    public function getACL($http_user, $writers, $readers)
    {
        return $this->getDefaultACL($http_user, $writers, $readers) . ',' . $this->getEffectiveACL($http_user, $writers, $readers);
    }

    private function getDefaultACL($http_user, $writers, $readers)
    {
        return 'd:' . implode(',d:', $this->getACLList($http_user, $writers, $readers));
    }

    protected function getACLReaders($label)
    {
        return "$label:rx";
    }

    protected function getACLWriters($label)
    {
        return "$label:rwx";
    }
}
