<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Bugzilla\Reference;

use DataAccess;

class Dao extends \DataAccessObject
{
    public function __construct(DataAccess $da = null)
    {
        parent::__construct($da);
        $this->enableExceptionsOnError();
    }

    public function save($keyword, $server, $username, $password, $are_followups_private)
    {
        $keyword               = $this->da->quoteSmart($keyword);
        $server                = $this->da->quoteSmart($server);
        $username              = $this->da->quoteSmart($username);
        $password              = $this->da->quoteSmart($password);
        $are_followups_private = $this->da->escapeInt($are_followups_private);

        $sql_save = "INSERT INTO plugin_bugzilla_reference(keyword, server, username, password, are_followup_private)
                      VALUES ($keyword, $server, $username, $password, $are_followups_private)";

        return $this->update($sql_save);
    }

    public function searchAllReferences()
    {
        $sql = "SELECT * FROM plugin_bugzilla_reference";

        return $this->retrieve($sql);
    }

    public function searchReferenceByKeyword($keyword)
    {
        $keyword = $this->da->quoteSmart($keyword);

        $sql = "SELECT * FROM plugin_bugzilla_reference WHERE keyword = $keyword";

        return $this->retrieveFirstRow($sql);
    }

    public function edit($id, $server, $username, $password, $are_followups_private)
    {
        $id                    = $this->da->escapeInt($id);
        $link                  = $this->da->quoteSmart($server . '/show_bug.cgi?id=$1');
        $server                = $this->da->quoteSmart($server);
        $username              = $this->da->quoteSmart($username);
        $password              = $this->da->quoteSmart($password);
        $are_followups_private = $this->da->escapeInt($are_followups_private);

        $this->da->startTransaction();

        $sql = "UPDATE plugin_bugzilla_reference SET
                  server = $server,
                  username = $username,
                  password = $password,
                  are_followup_private = $are_followups_private
                WHERE id = $id";

        $this->update($sql);


        $sql = "UPDATE reference AS ref
                    INNER JOIN plugin_bugzilla_reference AS bz ON (
                        bz.keyword = ref.keyword
                        AND ref.nature = 'bugzilla'
                        AND scope = 'S'
                    )
                SET ref.link = $link
                WHERE bz.id = $id";

        $this->update($sql);

        $this->commit();
    }

    public function getReferenceById($id)
    {
        $id = $this->da->escapeInt($id);

        $sql = "SELECT * FROM plugin_bugzilla_reference WHERE id = $id";

        return $this->retrieveFirstRow($sql);
    }

    public function delete($id)
    {
        $id = $this->da->escapeInt($id);

        $sql = "DELETE bugzilla, source_ref, target_ref, reference, reference_group
                FROM plugin_bugzilla_reference AS bugzilla
                    LEFT JOIN cross_references AS source_ref ON (
                        source_ref.source_type = 'bugzilla' AND source_ref.source_keyword = bugzilla.keyword
                    )
                    LEFT JOIN cross_references AS target_ref ON (
                        target_ref.target_type = 'bugzilla' AND target_ref.target_keyword = bugzilla.keyword
                    )
                    LEFT JOIN reference ON (
                        reference.keyword = bugzilla.keyword
                        AND reference.nature = 'bugzilla'
                        AND reference.scope = 'S'
                    )
                    LEFT JOIN reference_group ON (
                        reference.id = reference_group.reference_id
                    )
                WHERE bugzilla.id = $id";

        return $this->update($sql);
    }
}
