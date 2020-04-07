<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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
 * Base class for field post action DAOs.
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Transition_PostAction_CIBuildDao extends DataAccessObject
{

    /**
     * Create a new postaction entry
     *
     * @param int $transition_id The transition the post action belongs to
     * @param string $job_url       The job url
     *
     * @return int|false ID if success false otherwise
     */
    public function create($transition_id, $job_url)
    {
        $transition_id = $this->da->escapeInt($transition_id);
        $job_url       = $this->da->quoteSmart($job_url);

        $sql = "INSERT INTO tracker_workflow_transition_postactions_cibuild (transition_id, job_url)
                VALUES ($transition_id, $job_url)";

        return $this->updateAndGetLastId($sql);
    }

    public function searchByTransitionId($transition_id)
    {
        $transition_id = $this->da->escapeInt($transition_id);

        $sql = "SELECT *
                FROM tracker_workflow_transition_postactions_cibuild
                WHERE transition_id = $transition_id
                ORDER BY id";

        return $this->retrieve($sql);
    }

    /**
     * Update postaction entry
     *
     * @param int   $id       The id of the postaction
     * @param string $job_url The job url.
     *
     * @return bool true if success false otherwise
     */
    public function updatePostAction($id, $job_url)
    {
        $id       = $this->da->escapeInt($id);
        $job_url    = $this->da->quoteSmart($job_url);

        $sql = "UPDATE tracker_workflow_transition_postactions_cibuild
                SET job_url = $job_url
                WHERE id = $id";
        return $this->update($sql);
    }

    public function deletePostAction($id)
    {
        $id = $this->da->escapeInt($id);

        $sql = "DELETE FROM tracker_workflow_transition_postactions_cibuild
                WHERE id = $id";
        return $this->update($sql);
    }

    public function deletePostActionByTransition(int $transition_id): bool
    {
        $escaped_transition_id = $this->da->escapeInt($transition_id);

        $sql = "DELETE FROM tracker_workflow_transition_postactions_cibuild
                WHERE transition_id = $escaped_transition_id";
        return $this->update($sql);
    }

    /**
     * Duplicate a postaction
     *
     * @param int $from_transition_id The id of the template transition
     * @param int $to_transition_id The id of the transition
     *
     * @return bool true if success false otherwise
     */
    public function duplicate($from_transition_id, $to_transition_id)
    {
        $from_transition_id = $this->da->escapeInt($from_transition_id);
        $to_transition_id   = $this->da->escapeInt($to_transition_id);

        $sql = "INSERT INTO tracker_workflow_transition_postactions_cibuild(transition_id, job_url)
                SELECT $to_transition_id, job_url
                FROM tracker_workflow_transition_postactions_cibuild
                WHERE transition_id = $from_transition_id";

        return $this->update($sql);
    }
}
