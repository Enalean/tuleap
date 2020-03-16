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

abstract class ACLBuilder
{

    abstract public function getACL($http_user, $writers, $readers);

    abstract protected function getACLReaders($label);

    abstract protected function getACLWriters($label);

    protected function getEffectiveACL($http_user, $writers, $readers)
    {
        return implode(',', $this->getACLList($http_user, $writers, $readers));
    }

    protected function getACLList($http_user, $writers, $readers)
    {
        return array_filter(
            array(
                $this->getACLUserWriter($http_user),
                $this->getACLGroupWriters($writers),
                $this->getACLGroupReaders($readers)
            )
        );
    }

    protected function getACLUserWriter($user)
    {
        return "u:" . $this->getACLWriters($user);
    }

    protected function getACLGroupWriters($group)
    {
        if (trim($group)) {
            return "g:" . $this->getACLWriters($group);
        }
    }

    protected function getACLGroupReaders($group)
    {
        if (trim($group)) {
            return "g:" . $this->getACLReaders($group);
        }
        return '';
    }
}
