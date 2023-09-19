<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\MediawikiStandalone\Instance\Migration;

use Psr\Log\LoggerInterface;
use Tuleap\DB\DataAccessObject;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Option\Option;

final class LegacyMediawikiCreateMissingUsersDB extends DataAccessObject implements LegacyMediawikiCreateMissingUsers
{
    public function __construct(private readonly LegacyMediawikiCreateAndPromoteUser $create_and_promote_user)
    {
        parent::__construct();
    }

    /**
     * @psalm-return Ok<null>|Err<Fault>
     */
    public function create(LoggerInterface $logger, \Project $project, string $db_prefix): Ok|Err
    {
        $logger->info(self::class);

        $project_id = (int) $project->getID();

        return $this->getCurrentDatabaseName($project_id)->okOr(
            Result::err(Fault::fromMessage(sprintf('No current MediaWiki database found for project #%d', $project_id)))
        )->andThen(
            function (string $db_name) use ($logger, $project, $db_prefix) {
                return $this->createMissingUsers($logger, $project, $db_name, $db_prefix);
            }
        );
    }

    private function createMissingUsers(LoggerInterface $logger, \Project $project, string $db_name, string $db_prefix): Ok|Err
    {
        $logger->info('Create missing users');
        $db                             = $this->getDB();
        $escaped_mw_revision_table_name = $db->escapeIdentifier($db_prefix . 'revision');
        $escaped_db_name                = $db->escapeIdentifier($db_name);
        $results                        = $db->run(sprintf('SELECT DISTINCT rev_user_text FROM %s.%s WHERE rev_user = 0', $escaped_db_name, $escaped_mw_revision_table_name));
        foreach ($results as $row) {
            $logger->info(sprintf('Create %s', $row['rev_user_text']));
            $result = $this->create_and_promote_user->create($logger, $project, $row['rev_user_text']);
            if (Result::isErr($result)) {
                return $result;
            }
        }
        return $this->reassignPagesToUsers($logger, $db_name, $db_prefix);
    }

    private function reassignPagesToUsers(LoggerInterface $logger, string $db_name, string $db_prefix): Ok|Err
    {
        $logger->info('Reassign pages to users');
        $db                             = $this->getDB();
        $escaped_mw_revision_table_name = $db->escapeIdentifier($db_prefix . 'revision');
        $escaped_mw_user_table_name     = $db->escapeIdentifier($db_prefix . 'user');
        $escaped_db_name                = $db->escapeIdentifier($db_name);
        $this->getDB()->run(sprintf(
            'UPDATE %s.%s
            JOIN %s.%s ON (user_name = rev_user_text)
            SET rev_user = user_id
            WHERE rev_user = 0',
            $escaped_db_name,
            $escaped_mw_revision_table_name,
            $escaped_db_name,
            $escaped_mw_user_table_name,
        ));
        return Result::ok(null);
    }

    /**
     * @psalm-return Option<string>
     */
    private function getCurrentDatabaseName(int $project_id): Option
    {
        $database_name = $this->getDB()->single('SELECT database_name FROM plugin_mediawiki_database WHERE project_id = ?', [$project_id]);
        if ($database_name === false) {
            return Option::nothing(\Psl\Type\string());
        }
        return Option::fromValue($database_name);
    }
}
