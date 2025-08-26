<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\REST;

final class Cache
{
    private static ?self $instance = null;

    /** @var array<string, int> $project_ids */
    private array $project_ids = [];
    /** @var array<int, array<string, int>> $tracker_ids */
    private array $tracker_ids = [];
    /** @var array<int, array<string, string>> $user_groups_ids */
    private array $user_groups_ids = [];
    /** @var array<string, int> $user_ids */
    private array $user_ids = [];
    /** @var array<string, array{user_id: int, token: string}> $tokens */
    private array $tokens   = [];
    private array $trackers = [];
    /** @var array<int, list<array{id: int, title: string}>>  */
    private array $artifacts = [];

    public static function instance(): self
    {
        if (! isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getTrackerInProject(string $project_name, string $tracker_name): int
    {
        $project_id = $this->getProjectId($project_name);
        if (isset($this->tracker_ids[$project_id][$tracker_name])) {
            return $this->tracker_ids[$project_id][$tracker_name];
        }
        throw new \Exception('Tracker name does not exist in cache');
    }

    public function getProjectId(string $project_name): int
    {
        if (isset($this->project_ids[$project_name])) {
            return $this->project_ids[$project_name];
        }
        throw new \Exception('Project name not in cache');
    }

    /** @param array<string, int> $project_ids */
    public function setProjectIds(array $project_ids): void
    {
        $this->project_ids = $project_ids;
    }

    /** @return array<string, int> */
    public function getProjectIds(): array
    {
        return $this->project_ids;
    }

    /** @param array<int, array<string, int>> $tracker_ids */
    public function setTrackerIds(array $tracker_ids): void
    {
        $this->tracker_ids = $tracker_ids;
    }

    public function addTrackerRepresentations(array $tracker_representations): void
    {
        $this->trackers = $this->trackers + $tracker_representations;
    }

    public function getTrackerRepresentations(): array
    {
        return $this->trackers;
    }

    /** @return array<int, array<string, int>> */
    public function getTrackerIds(): array
    {
        return $this->tracker_ids;
    }

    /** @param array<int, array<string, string>> $user_groups_ids */
    public function setUserGroupIds(array $user_groups_ids): void
    {
        $this->user_groups_ids = $user_groups_ids;
    }

    /** @return array<int, array<string, string>> */
    public function getUserGroupIds(): array
    {
        return $this->user_groups_ids;
    }

    /** @param list<array{id: int, title: string}> $artifacts */
    public function setArtifacts(int $tracker_id, array $artifacts): void
    {
        $this->artifacts[$tracker_id] = $artifacts;
    }

    /** @return list<array{id: int, title: string}> | null */
    public function getArtifacts(int $tracker_id): ?array
    {
        return $this->artifacts[$tracker_id] ?? null;
    }

    /** @param array{user_id: int, token: string} $token */
    public function setTokenForUser(string $username, array $token): void
    {
        $this->tokens[$username] = $token;
    }

    /** @return array{user_id: int, token: string}|null */
    public function getTokenForUser(string $username): ?array
    {
        return $this->tokens[$username] ?? null;
    }

    /** @param array{id: int, username: string} $user */
    public function setUserId(array $user): void
    {
        $this->user_ids[$user['username']] = $user['id'];
    }

    /** @return array<string, int> */
    public function getUserIds(): array
    {
        return $this->user_ids;
    }
}
