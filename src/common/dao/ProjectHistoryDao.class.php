<?php
/**
 * Copyright (c) Enalean, 2018-Present. All rights reserved
 * Copyright (c) STMicroelectronics 2011. All rights reserved
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
 *  Data Access Object for Project history
 */
class ProjectHistoryDao extends \Tuleap\DB\DataAccessObject
{
    public function getHistory(
        \Project $project,
        int $offset,
        int $limit,
        ?string $event,
        ?array $sub_events,
        array $all_history_events,
        ?string $old_value,
        ?DateTimeImmutable $start_date,
        ?DateTimeImmutable $end_date,
        ?PFUser $by,
    ): array {
        $statement = \ParagonIE\EasyDB\EasyStatement::open()
            ->with('group_history.mod_by = user.user_id')
            ->andWith('group_id = ?', $project->getID());

        if ($by) {
            $statement->andWith('user.user_id = ?', $by->getId());
        }

        if ($start_date) {
            $statement->andWith('group_history.date > ?', $start_date->getTimestamp());
        }

        if ($end_date) {
            // Add 23:59:59 to timestamp
            $statement->andWith('group_history.date < ?', $end_date->getTimestamp() + 86399);
        }

        if ($old_value) {
            $statement->andWith('group_history.old_value LIKE ?', '%' . $this->getDB()->escapeLikeValue($old_value) . '%');
        }

        if (! empty($event) && strcmp($event, 'any')) {
            $group = $statement->group();
            if (! empty($sub_events)) {
                foreach ($sub_events as $key => $value) {
                    $group->orWith('group_history.field_name LIKE ?', $this->getDB()->escapeLikeValue($key) . '%');
                }
            } else {
                foreach ($all_history_events[$event] as $key => $value) {
                    $group->orWith('group_history.field_name LIKE ?', $this->getDB()->escapeLikeValue($value) . '%');
                }
            }
        }

        $query = "SELECT group_history.field_name,
              group_history.old_value,
              group_history.date,
              user.user_name
          FROM group_history, user
          WHERE $statement";

        if ($offset > 0 || $limit > 0) {
            $history = $this->getDB()->safeQuery("$query ORDER BY group_history.date DESC LIMIT ?, ?", [...$statement->values(), $offset, $limit]);
        } else {
            $history = $this->getDB()->run("$query ORDER BY group_history.date DESC", ...$statement->values());
        }
        $numrows = $this->getDB()->single("SELECT count(*) FROM group_history, user WHERE $statement", $statement->values());

        return [
            'history' => $history,
            'numrows' => $numrows,
        ];
    }

    /**
     * handle the insertion of history for corresponding  parameters
     * $args is an array containing a list of parameters to use when
     * the message is to be displayed by the history.php script
     * The array is stored as a string at the end of the field_name
     * with the following format:
     * field_name %% [arg1, arg2...]
     *
     * @deprecated Use addHistory() instead
     * @param String  $fieldName Event category
     * @param String  $oldValue  Event value
     * @param int $groupId Project ID
     * @param Array|false   $args      list of parameters used for message display
     */
    public function groupAddHistory($fieldName, $oldValue, $groupId, $args = false): void
    {
        $this->addHistory(
            ProjectManager::instance()->getProjectById($groupId),
            UserManager::instance()->getCurrentUser(),
            new DateTimeImmutable('@' . $_SERVER['REQUEST_TIME']),
            $fieldName,
            $oldValue,
            $args ?: [],
        );
    }

    public function addHistory(
        \Project $project,
        \PFUser $project_admin,
        \DateTimeImmutable $now,
        string $field_name,
        string $old_value,
        array $args = [],
    ): void {
        if ($args) {
            $field_name .= ' %% ' . implode('||', $args);
        }

        $mod_by = $project_admin->getId() ?: 100;

        $this->getDB()->insert(
            'group_history',
            [
                'group_id'   => $project->getID(),
                'field_name' => $field_name,
                'old_value'  => $old_value,
                'mod_by'     => $mod_by,
                'date'       => $now->getTimestamp(),
            ]
        );
    }
}
