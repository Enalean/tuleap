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

namespace Tuleap\ProgramManagement\Domain\Team\Creation;

use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * @psalm-immutable
 */
final class Team
{
    /**
     * @var int
     */
    private $team_id;

    private function __construct(int $team_id)
    {
        $this->team_id = $team_id;
    }

    public function getTeamId(): int
    {
        return $this->team_id;
    }

    /**
     * @throws \Tuleap\ProgramManagement\Domain\Team\ProjectIsAProgramException
     * @throws \Tuleap\ProgramManagement\Domain\Team\TeamAccessException
     */
    public static function build(BuildTeam $build_team, int $team_id, UserIdentifier $user_identifier): self
    {
        $build_team->checkProjectIsATeam($team_id, $user_identifier);

        return new self($team_id);
    }

    /**
     * This method has meaning only while we can't do test initialization by xml import.
     * If build() is used, it breaks because REQUEST_URI is null.
     * @throws \Tuleap\ProgramManagement\Domain\Team\ProjectIsAProgramException
     * @throws \Tuleap\ProgramManagement\Domain\Team\TeamAccessException
     */
    public static function buildForRestTest(BuildTeam $build_team, int $team_id): self
    {
        $build_team->checkProjectIsATeamForRestTestInitialization($team_id);

        return new self($team_id);
    }
}
