<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Project\Banner;

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DataAccessObject;

class BannerDao extends DataAccessObject
{
    private const USER_PREFERENCE_NAME_START = 'project_banner_';

    public function addBanner(int $project_id, string $message): void
    {
        $this->getDB()->tryFlatTransaction(static function (EasyDB $db) use ($project_id, $message): void {
            $db->run(
                'REPLACE INTO project_banner(project_id, message) VALUES (?, ?)',
                $project_id,
                $message
            );
            self::removeUserBannerPreferenceForProjectID($db, $project_id);
        });
    }

    public function deleteBanner(int $project_id): void
    {
        $this->getDB()->tryFlatTransaction(static function (EasyDB $db) use ($project_id): void {
            $db->run('DELETE FROM project_banner WHERE project_id = ?', $project_id);
            self::removeUserBannerPreferenceForProjectID($db, $project_id);
        });
    }

    public function searchBannerByProjectId(int $project_id): ?string
    {
        $sql = "SELECT message
            FROM project_banner
            WHERE project_id=?";

        return $this->getDB()->cell($sql, $project_id) ?: null;
    }

    /**
     * @psalm-return array{message: string, preference_value: string|null}|null
     */
    public function searchBannerWithVisibilityByProjectID(int $project_id, int $user_id): ?array
    {
        $sql = 'SELECT message, preference_value
                FROM project_banner
                LEFT JOIN user_preferences ON (preference_name = ? AND user_id = ?)
                WHERE project_id = ?';

        return $this->getDB()->row($sql, self::getUserPreferenceForProjectID($project_id), $user_id, $project_id);
    }

    private static function removeUserBannerPreferenceForProjectID(EasyDB $db, int $project_id): void
    {
        $db->run(
            'DELETE FROM user_preferences WHERE preference_name = ?',
            self::getUserPreferenceForProjectID($project_id)
        );
    }

    private static function getUserPreferenceForProjectID(int $project_id): string
    {
        return self::USER_PREFERENCE_NAME_START . $project_id;
    }
}
