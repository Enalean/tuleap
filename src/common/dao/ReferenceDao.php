<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Reference\GetSystemReferenceNatureByKeyword;

class ReferenceDao extends DataAccessObject implements GetSystemReferenceNatureByKeyword // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    private const array NOT_ANYMORE_SUPPORTED_NATURES = [
        'artifact', // Tracker v3
        'news',
        'forum',
        'forum_message',
        'cvs_commit',
    ];

    /**
    * Gets all references for the given project ID, sorted for presentation
    * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
    */
    public function searchByGroupID($group_id)
    {
        $exclusion = implode(',', array_map($this->da->quoteSmart(...), self::NOT_ANYMORE_SUPPORTED_NATURES));
        $sql       = sprintf(
            'SELECT * FROM reference,reference_group WHERE reference_group.group_id=%s AND reference_group.reference_id=reference.id AND reference.nature NOT IN (%s) ORDER BY reference.scope DESC, reference.service_short_name, reference.keyword',
            $this->da->quoteSmart($group_id),
            $exclusion,
        );
        return $this->retrieve($sql);
    }

    /**
    * Gets a reference from the reference id and the group ID, so that we also have "is_active" row
    * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
    */
    public function searchByIdAndGroupID($ref_id, $group_id)
    {
        $exclusion = implode(',', array_map($this->da->quoteSmart(...), self::NOT_ANYMORE_SUPPORTED_NATURES));
        $sql       = sprintf(
            'SELECT * FROM reference,reference_group WHERE reference_group.group_id=%s AND reference.id=%s AND reference_group.reference_id=reference.id AND reference.nature NOT IN (%s)',
            $this->da->quoteSmart($group_id),
            $this->da->quoteSmart($ref_id),
            $exclusion,
        );
        return $this->retrieve($sql);
    }

    /**
    * Gets all active references for the given project ID
    * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
    */
    public function searchActiveByGroupID($group_id)
    {
        $exclusion = implode(',', array_map($this->da->quoteSmart(...), self::NOT_ANYMORE_SUPPORTED_NATURES));
        $sql       = sprintf(
            'SELECT * FROM reference,reference_group WHERE reference_group.group_id=%s AND reference_group.reference_id=reference.id AND reference_group.is_active=1 AND reference.nature NOT IN (%s)',
            $this->da->quoteSmart($group_id),
            $exclusion,
        );
        return $this->retrieve($sql);
    }

    /**
    * Gets all tables of the db
    * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
    */
    public function searchAll()
    {
        $sql = 'SELECT * FROM reference';
        return $this->retrieve($sql);
    }

    /**
    * Searches Reference by reference Id
    * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
    */
    public function searchById($id)
    {
        $sql = sprintf(
            'SELECT * FROM reference WHERE id = %s',
            $this->da->quoteSmart($id)
        );
        return $this->retrieve($sql);
    }

    /**
     * @return array|false
     */
    public function searchByKeyword($keyword)
    {
        $exclusion = implode(',', array_map($this->da->quoteSmart(...), self::NOT_ANYMORE_SUPPORTED_NATURES));
        $keyword   = $this->da->quoteSmart($keyword);

        $sql = "SELECT *
                FROM reference
                JOIN reference_group
                    ON reference_group.reference_id = reference.id
                WHERE reference.keyword = $keyword AND reference.nature NOT IN ($exclusion)";

        return $this->retrieveFirstRow($sql);
    }

    /**
    * Searches Reference by scope
    * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
    */
    public function searchByScope($scope)
    {
        $exclusion = implode(',', array_map($this->da->quoteSmart(...), self::NOT_ANYMORE_SUPPORTED_NATURES));
        $sql       = sprintf(
            'SELECT * FROM reference WHERE scope = %s AND reference.nature NOT IN (%s)',
            $this->da->quoteSmart($scope),
            $exclusion,
        );
        return $this->retrieve($sql);
    }

    /**
     * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
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

    #[Override]
    public function getSystemReferenceNatureByKeyword($keyword): false|array
    {
        $exclusion = implode(',', array_map($this->da->quoteSmart(...), self::NOT_ANYMORE_SUPPORTED_NATURES));
        $keyword   = $this->da->quoteSmart($keyword);

        $sql = "SELECT nature
                FROM reference
                WHERE keyword = $keyword
                  AND scope = 'S'
                  AND reference.nature NOT IN ($exclusion)";

        return $this->retrieveFirstRow($sql);
    }

    /**
     * Searches Reference by service short name
     *
     * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface|false
     */
    public function searchByServiceShortName(int $project_id, string $service)
    {
        $exclusion = implode(',', array_map($this->da->quoteSmart(...), self::NOT_ANYMORE_SUPPORTED_NATURES));

        $sql = sprintf(
            'SELECT reference.*
             FROM reference
                JOIN reference_group rg ON (reference.id = rg.reference_id)
             WHERE service_short_name = %s
               AND rg.group_id = %d
               AND reference.nature NOT IN (%s)',
            $this->da->quoteSmart($service),
            $this->da->escapeInt($project_id),
            $exclusion,
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches Reference by scope and service short name
    * Don't return reference 100 (empty reference)
    * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
    */
    public function searchByScopeAndServiceShortName($scope, $service)
    {
        $exclusion = implode(',', array_map($this->da->quoteSmart(...), self::NOT_ANYMORE_SUPPORTED_NATURES));

        $sql = sprintf(
            'SELECT * FROM reference WHERE scope = %s AND service_short_name = %s AND id != 100 AND reference.nature NOT IN (%s)',
            $this->da->quoteSmart($scope),
            $this->da->quoteSmart($service),
            $exclusion,
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches Reference by scope and service short name
    * Don't return reference 100 (empty reference)
    * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
    */
    public function searchByScopeAndServiceShortNameAndGroupId($scope, $service, $group_id)
    {
        $exclusion = implode(',', array_map($this->da->quoteSmart(...), self::NOT_ANYMORE_SUPPORTED_NATURES));

        $sql = sprintf(
            'SELECT * FROM reference,reference_group WHERE scope = %s AND reference.id=reference_group.reference_id AND service_short_name = %s AND group_id = %s AND reference.id != 100 AND reference.nature NOT IN (%s)',
            $this->da->quoteSmart($scope),
            $this->da->quoteSmart($service),
            $this->da->quoteSmart($group_id),
            $exclusion,
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches Reference by keyword and group_id
    * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface with one field ('reference_id')
    */
    public function searchByKeywordAndGroupId($keyword, $group_id)
    {
        $exclusion = implode(',', array_map($this->da->quoteSmart(...), self::NOT_ANYMORE_SUPPORTED_NATURES));
        // Order by scope to return 'P'roject references before 'S'ystem references
        // This may happen for old services created before Reference management.
        // Otherwise, there should not be both S and P reference with the same keyword...
        $sql = sprintf(
            'SELECT * FROM reference,reference_group WHERE reference.keyword = %s and reference.id=reference_group.reference_id and reference_group.group_id=%s ORDER BY reference.scope AND reference.nature NOT IN (%s)',
            $this->da->quoteSmart($keyword),
            $this->da->quoteSmart($group_id),
            $exclusion,
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches Reference by keyword and group_id
    * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface with one field ('reference_id')
    */
    public function searchByKeywordAndGroupIdAndDescriptionAndLinkAndScope($keyword, $group_id, $description, $link, $scope)
    {
        $exclusion = implode(',', array_map($this->da->quoteSmart(...), self::NOT_ANYMORE_SUPPORTED_NATURES));
        // Order by scope to return 'P'roject references before 'S'ystem references
        // This may happen for old services created before Reference management.
        // Otherwise, there should not be both S and P reference with the same keyword...
        $sql = sprintf(
            'SELECT * FROM reference r,reference_group rg WHERE ' .
               "service_short_name != 'plugin_tracker' AND " .
               'r.keyword = %s AND ' .
               'r.id=rg.reference_id AND ' .
               'rg.group_id=%s AND ' .
               'r.description = %s AND ' .
               'r.link = %s AND ' .
               'r.scope = %s AND r.nature NOT IN (%s)',
            $this->da->quoteSmart($keyword),
            $this->da->quoteSmart($group_id),
            $this->da->quoteSmart($description),
            $this->da->quoteSmart($link),
            $this->da->quoteSmart($scope),
            $exclusion,
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
            'INSERT INTO reference (keyword,description,link,scope,service_short_name, nature) VALUES (%s, %s, %s, %s, %s, %s);',
            $this->da->quoteSmart($keyword),
            $this->da->quoteSmart($desc),
            $this->da->quoteSmart($link),
            $this->da->quoteSmart($scope),
            $this->da->quoteSmart($service_short_name),
            $this->da->quoteSmart($nature)
        );
        return $this->updateAndGetLastId($sql);
    }

    public function create_ref_group($refid, $is_active, $group_id) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $sql = sprintf(
            'INSERT INTO reference_group (reference_id,is_active,group_id) VALUES (%s, %s, %s);',
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
    public function update_ref($id, $keyword, $desc, $link, $scope, $service_short_name, $nature) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $sql = sprintf(
            'UPDATE reference SET keyword=%s, description=%s, link=%s, scope=%s, service_short_name=%s, nature=%s WHERE id=%s;',
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

    public function update_ref_group($refid, $is_active, $group_id) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $sql = sprintf(
            'UPDATE reference_group SET is_active=%s WHERE reference_id=%s AND group_id=%s;',
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

    public function removeById($id)
    {
        $sql = sprintf(
            'DELETE FROM reference WHERE id = %s',
            $this->da->quoteSmart($id)
        );
        return $this->update($sql);
    }

    public function removeRefGroup($id, $group_id)
    {
        $sql = sprintf(
            'DELETE FROM reference_group WHERE reference_id = %s AND group_id = %s',
            $this->da->quoteSmart($id),
            $this->da->quoteSmart($group_id)
        );
        return $this->update($sql);
    }

    public function removeAllById($id)
    {
        $sql = sprintf(
            'DELETE reference, reference_group FROM reference, reference_group WHERE reference.id = %s AND reference_group.reference_id =%s',
            $this->da->quoteSmart($id),
            $this->da->quoteSmart($id)
        );
        return $this->update($sql);
    }
}
