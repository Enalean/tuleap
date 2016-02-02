<?php
/**
    * Copyright (c) Enalean, 2016. All rights reserved
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

class SVN_DAO extends DataAccessObject {

    private $event_manager;

    public function __construct() {
        parent::__construct();
        $this->event_manager = EventManager::instance();
    }

    public function searchSvnRepositories() {
        $auth_mod = $this->da->quoteSmart(ForgeConfig::get(SVN_Apache_SvnrootConf::CONFIG_SVN_AUTH_KEY));

        $sql = "SELECT groups.*, service.*, unix_group_name AS repository_name, $auth_mod AS auth_mod
                FROM groups, service
                WHERE groups.group_id = service.group_id
                  AND service.short_name = 'svn'
                  AND service.is_used = '1'
                  AND groups.status = 'A'";

        $sql_fragments = array($sql);

        $this->event_manager->processEvent(
            Event::GET_SVN_LIST_REPOSITORIES_SQL_FRAGMENTS,
            array(
                'sql_fragments' => &$sql_fragments
            )
        );

        return $this->retrieve(implode(' UNION ', $sql_fragments));
    }
}