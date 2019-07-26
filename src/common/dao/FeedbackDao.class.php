<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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


/**
 *  Data Access Object for Feedback
 */
class FeedbackDao extends DataAccessObject
{
    /**
    * Searches Feedback
    * @return DataAccessResult
    */
    public function search($session_id)
    {
        $session_id = $this->da->escapeInt($session_id);

        $sql = "SELECT * FROM feedback WHERE session_id = $session_id";

        return $this->retrieve($sql);
    }

    public function create($session_id, array $feedback)
    {
        $session_id          = $this->da->escapeInt($session_id);
        $serialized_feedback = $this->da->quoteSmart(json_encode($feedback));

        $sql = "REPLACE INTO feedback (session_id, feedback, created_at) VALUES ($session_id, $serialized_feedback, NOW())";

        return $this->update($sql);
    }

    /**
    * delete a row in the table Feedback
    * @return true if there is no error
    */
    public function delete($session_id)
    {
        $session_id = $this->da->escapeInt($session_id);

        $sql = "DELETE FROM feedback WHERE session_id = $session_id";

        return $this->update($sql);
    }
}
