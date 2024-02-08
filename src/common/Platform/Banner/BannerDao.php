<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Platform\Banner;

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DataAccessObject;

/**
 * @psalm-import-type BannerImportance from \Tuleap\Platform\Banner\Banner
 */
class BannerDao extends DataAccessObject
{
    private const USER_PREFERENCE_NAME = 'platform_banner';

    /**
     * @psalm-param BannerImportance $importance
     */
    public function addBanner(string $message, string $importance, ?\DateTimeImmutable $expiration_date): void
    {
        $this->getDB()->tryFlatTransaction(
            static function (EasyDB $db) use ($message, $importance, $expiration_date): void {
                $db->run('DELETE FROM platform_banner');
                $expiration_date_timestamp = null;
                if ($expiration_date !== null) {
                    $expiration_date_timestamp = $expiration_date->getTimestamp();
                }
                $db->run(
                    'INSERT INTO platform_banner(message, importance, expiration_date) VALUES (?, ?, ?)',
                    $message,
                    $importance,
                    $expiration_date_timestamp
                );
                self::removeUserBannerPreference($db);
            }
        );
    }

    public function deleteBanner(): void
    {
        $this->getDB()->tryFlatTransaction(
            static function (EasyDB $db): void {
                $db->run('DELETE FROM platform_banner');
                self::removeUserBannerPreference($db);
            }
        );
    }

    /**
     * @psalm-return array{message: string, importance: BannerImportance, expiration_date: ?int}|null
     */
    public function searchBanner(): ?array
    {
        $sql = 'SELECT message, importance, expiration_date FROM platform_banner';

        return $this->getDB()->row($sql) ?: null;
    }

    /**
     * @psalm-return array{message: string, importance: BannerImportance, preference_value: string|null}|null
     */
    public function searchNonExpiredBannerWithVisibility(int $user_id, \DateTimeImmutable $current_time): ?array
    {
        $sql = 'SELECT message, importance, preference_value
                FROM platform_banner
                LEFT JOIN user_preferences ON (preference_name = ? AND user_id = ?)
                WHERE platform_banner.expiration_date > ? OR platform_banner.expiration_date IS NULL';

        return $this->getDB()->row($sql, self::USER_PREFERENCE_NAME, $user_id, $current_time->getTimestamp());
    }

    private static function removeUserBannerPreference(EasyDB $db): void
    {
        $db->run(
            'DELETE FROM user_preferences WHERE preference_name = ?',
            self::USER_PREFERENCE_NAME
        );
    }
}
