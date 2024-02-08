<?php
/**
 * Copyright Enalean (c) 2017 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Semantic\Status\Done;

use PDOException;
use Tuleap\DB\DataAccessObject;

class SemanticDoneDao extends DataAccessObject
{
    /**
     * @psalm-return list<array{value_id: int}>
     */
    public function getSelectedValues(int $tracker_id): array
    {
        $sql = 'SELECT value_id
                FROM plugin_tracker_semantic_done
                WHERE tracker_id = ?';

        return $this->getDB()->run($sql, $tracker_id);
    }

    public function isValueADoneValue(int $tracker_id, int $value_id): bool
    {
        $sql = 'SELECT NULL
                FROM plugin_tracker_semantic_done
                WHERE tracker_id = ?
                  AND value_id = ?';

        $rows = $this->getDB()->run($sql, $tracker_id, $value_id);

        return count($rows) > 0;
    }

    public function clearForTracker(int $tracker_id): void
    {
        $this->getDB()->delete(
            'plugin_tracker_semantic_done',
            [
                'tracker_id' => $tracker_id,
            ]
        );
    }

    public function addForTracker(int $tracker_id, array $selected_values): bool
    {
        try {
            $values = [];
            foreach ($selected_values as $value_id) {
                $values[] = [
                    'tracker_id' => $tracker_id,
                    'value_id' => $value_id,
                ];
            }

            $this->getDB()->insertMany(
                'plugin_tracker_semantic_done',
                $values
            );

            return true;
        } catch (PDOException $exception) {
            return false;
        }
    }

    public function updateForTracker(int $tracker_id, array $selected_values): void
    {
        $this->getDB()->tryFlatTransaction(function () use ($tracker_id, $selected_values): void {
            $this->clearForTracker($tracker_id);
            $this->addForTracker($tracker_id, $selected_values);
        });
    }
}
