<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('include/DataAccessObject.class.php');

/**
 *  Data Access Object for Reference
 */
class ReferenceDao extends DataAccessObject
{
    /**
    * Gets all references for the given project ID, sorted for presentation
    * @return DataAccessResult
    */
    public function searchByGroupID($group_id)
    {
        $sql = sprintf(
            "SELECT * FROM reference,reference_group WHERE reference_group.group_id=%s AND reference_group.reference_id=reference.id ORDER BY reference.scope DESC, reference.service_short_name, reference.keyword",
            $this->da->quoteSmart($group_id)
        );
        return $this->retrieve($sql);
    }

    /**
    * Gets a reference from the reference id and the group ID, so that we also have "is_active" row
    * @return DataAccessResult
    */
    public function searchByIdAndGroupID($ref_id, $group_id)
    {
        $sql = sprintf(
            "SELECT * FROM reference,reference_group WHERE reference_group.group_id=%s AND reference.id=%s AND reference_group.reference_id=reference.id",
            $this->da->quoteSmart($group_id),
            $this->da->quoteSmart($ref_id)
        );
        return $this->retrieve($sql);
    }

    /**
    * Gets all active references for the given project ID
    * @return DataAccessResult
    */
    public function searchActiveByGroupID($group_id)
    {
        $sql = sprintf(
            "SELECT * FROM reference,reference_group WHERE reference_group.group_id=%s AND reference_group.reference_id=reference.id AND reference_group.is_active=1",
            $this->da->quoteSmart($group_id)
        );
        return $this->retrieve($sql);
    }

    /**
    * Gets all tables of the db
    * @return DataAccessResult
    */
    public function searchAll()
    {
        $sql = "SELECT * FROM reference";
        return $this->retrieve($sql);
    }

    /**
    * Searches Reference by reference Id
    * @return DataAccessResult
    */
    public function searchById($id)
    {
        $sql = sprintf(
            "SELECT * FROM reference WHERE id = %s",
            $this->da->quoteSmart($id)
        );
        return $this->retrieve($sql);
    }

    /**
     * @return DataAccessResult
     */
    public function searchByKeyword($keyword)
    {
        $keyword = $this->da->quoteSmart($keyword);

        $sql = "SELECT *
                FROM reference
                JOIN reference_group
                    ON reference_group.reference_id = reference.id
                WHERE reference.keyword = $keyword";

        return $this->retrieveFirstRow($sql);
    }

    /**
    * Searches Reference by scope
    * @return DataAccessResult
    */
    public function searchByScope($scope)
    {
        $sql = sprintf(
            "SELECT * FROM reference WHERE scope = %s",
            $this->da->quoteSmart($scope)
        );
        return $this->retrieve($sql);
    }

    /**
     * @return DataAccessResult
     */
    public function searchSystemReferenceByNatureAndKeyword($keyword, $nature)
    {
        $keyword = $this->da->quoteSmart($keyword);
        $nature  = $this->da->quoteSmart($nature);

        $sql = "SELECT *
                FROM reference
                WHERE keyword = $keyword
                  AND nature = $nature
                  AND scope = 'S'";

        return $this->retrieve($sql);
    }

    /**
     * @return DataAccessResult
     */
    public function getSystemReferenceByNatureAndKeyword($keyword, $nature)
    {
        $keyword = $this->da->quoteSmart($keyword);
        $nature  = $this->da->quoteSmart($nature);

        $sql = "SELECT *
                FROM reference
                WHERE keyword = $keyword
                  AND nature = $nature
                  AND scope = 'S'";

        return $this->retrieveFirstRow($sql);
    }

    /**
     * @return DataAccessResult
     */
    public function getSystemReferenceNatureByKeyword($keyword)
    {
        $keyword = $this->da->quoteSmart($keyword);

        $sql = "SELECT nature
                FROM reference
                WHERE keyword = $keyword
                  AND scope = 'S'";

        return $this->retrieveFirstRow($sql);
    }


    /**
    * Searches Reference by service short name
    * @return DataAccessResult
    */
    public function searchByServiceShortName($service)
    {
        $sql = sprintf(
            "SELECT * FROM reference WHERE service_short_name = %s",
            $this->da->quoteSmart($service)
        );
        return $this->retrieve($sql);
    }


    /**
    * Searches Reference by scope and service short name
    * Don't return reference 100 (empty reference)
    * @return DataAccessResult
    */
    public function searchByScopeAndServiceShortName($scope, $service)
    {
        $sql = sprintf(
            "SELECT * FROM reference WHERE scope = %s AND service_short_name = %s AND id != 100",
            $this->da->quoteSmart($scope),
            $this->da->quoteSmart($service)
        );
        return $this->retrieve($sql);
    }


    /**
    * Searches Reference by scope and service short name
    * Don't return reference 100 (empty reference)
    * @return DataAccessResult
    */
    public function searchByScopeAndServiceShortNameAndGroupId($scope, $service, $group_id)
    {
        $sql = sprintf(
            "SELECT * FROM reference,reference_group WHERE scope = %s AND reference.id=reference_group.reference_id AND service_short_name = %s AND group_id = %s AND reference.id != 100",
            $this->da->quoteSmart($scope),
            $this->da->quoteSmart($service),
            $this->da->quoteSmart($group_id)
        );
        return $this->retrieve($sql);
    }


    /**
    * Searches Reference by keyword and group_id
    * @return DataAccessResult with one field ('reference_id')
    */
    public function searchByKeywordAndGroupId($keyword, $group_id)
    {
        // Order by scope to return 'P'roject references before 'S'ystem references
        // This may happen for old tracker created before Reference management.
        // Otherwise, there should not be both S and P reference with the same keyword...
        $sql = sprintf(
            "SELECT * FROM reference,reference_group WHERE reference.keyword = %s and reference.id=reference_group.reference_id and reference_group.group_id=%s ORDER BY reference.scope",
            $this->da->quoteSmart($keyword),
            $this->da->quoteSmart($group_id)
        );
        return $this->retrieve($sql);
    }


    /**
    * Searches Reference by keyword and group_id
    * @return DataAccessResult with one field ('reference_id')
    */
    public function searchByKeywordAndGroupIdAndDescriptionAndLinkAndScope($keyword, $group_id, $description, $link, $scope)
    {
        // Order by scope to return 'P'roject references before 'S'ystem references
        // This may happen for old tracker created before Reference management.
        // Otherwise, there should not be both S and P reference with the same keyword...
        $sql = sprintf(
            "SELECT * FROM reference r,reference_group rg WHERE " .
               "service_short_name != 'plugin_tracker' AND " .
               "r.keyword = %s AND " .
               "r.id=rg.reference_id AND " .
               "rg.group_id=%s AND " .
               "r.description = %s AND " .
               "r.link = %s AND " .
               "r.scope = %s",
            $this->da->quoteSmart($keyword),
            $this->da->quoteSmart($group_id),
            $this->da->quoteSmart($description),
            $this->da->quoteSmart($link),
            $this->da->quoteSmart($scope)
        );
        return $this->retrieve($sql);
    }



    /**
    * create a row in the table reference
    * @return true or id(auto_increment) if there is no error
    */
    public function create($keyword, $desc, $link, $scope, $service_short_name, $nature)
    {
        $sql = sprintf(
            "INSERT INTO reference (keyword,description,link,scope,service_short_name, nature) VALUES (%s, %s, %s, %s, %s, %s);",
            $this->da->quoteSmart($keyword),
            $this->da->quoteSmart($desc),
            $this->da->quoteSmart($link),
            $this->da->quoteSmart($scope),
            $this->da->quoteSmart($service_short_name),
            $this->da->quoteSmart($nature)
        );
        return $this->updateAndGetLastId($sql);
    }

    public function create_ref_group($refid, $is_active, $group_id)
    {
        $sql = sprintf(
            "INSERT INTO reference_group (reference_id,is_active,group_id) VALUES (%s, %s, %s);",
            $this->da->quoteSmart($refid),
            $this->da->quoteSmart($is_active),
            $this->da->quoteSmart($group_id)
        );
        return $this->updateAndGetLastId($sql);
    }

    /**
    * update a row in the table reference
    * @return true or id(auto_increment) if there is no error
    */
    public function update_ref($id, $keyword, $desc, $link, $scope, $service_short_name, $nature)
    {
        $sql = sprintf(
            "UPDATE reference SET keyword=%s, description=%s, link=%s, scope=%s, service_short_name=%s, nature=%s WHERE id=%s;",
            $this->da->quoteSmart($keyword),
            $this->da->quoteSmart($desc),
            $this->da->quoteSmart($link),
            $this->da->quoteSmart($scope),
            $this->da->quoteSmart($service_short_name),
            $this->da->quoteSmart($nature),
            $this->da->quoteSmart($id)
        );
        return $this->update($sql);
    }

    public function update_ref_group($refid, $is_active, $group_id)
    {
        $sql = sprintf(
            "UPDATE reference_group SET is_active=%s WHERE reference_id=%s AND group_id=%s;",
            $this->da->quoteSmart($is_active),
            $this->da->quoteSmart($refid),
            $this->da->quoteSmart($group_id)
        );
        return $this->update($sql);
    }

    public function updateProjectReferenceShortName($group_id, $old_short_name, $new_short_name)
    {
        $group_id       = $this->da->escapeInt($group_id);
        $old_short_name = $this->da->quoteSmart($old_short_name);
        $new_short_name = $this->da->quoteSmart($new_short_name);

        $sql = "UPDATE  reference r
                      INNER JOIN reference_group rg ON (r.id=rg.reference_id)
                SET r.keyword=$new_short_name
                WHERE r.keyword=$old_short_name AND rg.group_id=$group_id";
        return $this->update($sql);
    }

    public function update_keyword($old_keyword, $keyword, $group_id)
    {
        $sql = sprintf(
            "UPDATE reference, reference_group SET keyword=%s WHERE reference.keyword = %s and reference.id=reference_group.reference_id and reference_group.group_id=%s",
            $this->da->quoteSmart($keyword),
            $this->da->quoteSmart($old_keyword),
            $this->da->quoteSmart($group_id)
        );
        return $this->update($sql);
    }

    public function removeById($id)
    {
        $sql = sprintf(
            "DELETE FROM reference WHERE id = %s",
            $this->da->quoteSmart($id)
        );
        return $this->update($sql);
    }

    public function removeRefGroup($id, $group_id)
    {
        $sql = sprintf(
            "DELETE FROM reference_group WHERE reference_id = %s AND group_id = %s",
            $this->da->quoteSmart($id),
            $this->da->quoteSmart($group_id)
        );
        return $this->update($sql);
    }

    public function removeAllById($id)
    {
        $sql = sprintf(
            "DELETE reference, reference_group FROM reference, reference_group WHERE reference.id = %s AND reference_group.reference_id =%s",
            $this->da->quoteSmart($id),
            $this->da->quoteSmart($id)
        );
        return $this->update($sql);
    }
}
