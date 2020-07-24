<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\LDAP;

use LDAP_UserDao;

class NonUniqueUidRetriever
{

    /**
     * @var LDAP_UserDao
     */
    private $dao;

    public function __construct(LDAP_UserDao $dao)
    {
        $this->dao = $dao;
    }

    public function getNonUniqueLdapUid()
    {
        $rows      = $this->dao->searchNonUniqueLdapUid();
        $ldap_uids = [];

        foreach ($rows as $row) {
            $ldap_uids[] = $row['ldap_uid'];
        }

        return $ldap_uids;
    }
}
