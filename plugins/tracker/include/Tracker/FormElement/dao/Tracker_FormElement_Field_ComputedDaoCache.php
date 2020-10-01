<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

/**
 * Cache computation for better performances
 */
class Tracker_FormElement_Field_ComputedDaoCache
{
    private static $instance;

    private $dao;

    public function __construct(Tracker_FormElement_Field_ComputedDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return Tracker_FormElement_Field_ComputedDaoCache
     */
    public static function instance()
    {
        if (! self::$instance) {
            $class = self::class;
            self::$instance = new $class(new Tracker_FormElement_Field_ComputedDao());
        }
        return self::$instance;
    }

    /**
     * @return false | int
     */
    public function getCachedFieldValueAtTimestamp($artifact_id, $field_id, $timestamp)
    {
        $row = $this->dao->getCachedFieldValueAtTimestamp($artifact_id, $field_id, $timestamp);

        return ($row) ? $row['value'] : false;
    }

    /**
     * @return bool
     */
    public function saveCachedFieldValueAtTimestamp($artifact_id, $field_id, $timestamp, $value)
    {
        return $this->dao->saveCachedFieldValueAtTimestamp($artifact_id, $field_id, $timestamp, $value);
    }

    public function deleteArtifactCacheValue($artifact_id, $field_id)
    {
        return $this->dao->deleteArtifactCacheValue($artifact_id, $field_id);
    }

    public function deleteAllArtifactCacheValues(Tracker_Artifact $artifact)
    {
        return $this->dao->deleteAllArtifactCacheValues($artifact->getId());
    }
}
