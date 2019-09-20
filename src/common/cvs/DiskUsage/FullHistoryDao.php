<?php
/**
 *
 *
 *
 * Time: 10:48 AM
 */

namespace Tuleap\CVS\DiskUsage;

use DataAccessObject;

class FullHistoryDao extends DataAccessObject
{
    public function hasRepositoriesUpdatedAfterGivenDate($project_id, $date)
    {
        $project_id = $this->da->escapeInt($project_id);
        $date       = $this->da->escapeInt($date);

        $sql = "SELECT NULL
                FROM group_cvs_full_history
                WHERE group_id = $project_id
                  AND day > $date";

        return $this->retrieve($sql)->count() > 0;
    }
}
