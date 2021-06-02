<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Team\MirroredTimeboxes;

use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxArtifactLinkType;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredTimebox;

class MirroredTimeboxRetriever
{
    /**
     * @var MirroredTimeboxesDao
     */
    private $mirrored_milestones_dao;

    public function __construct(MirroredTimeboxesDao $mirrored_milestones_dao)
    {
        $this->mirrored_milestones_dao = $mirrored_milestones_dao;
    }

    /**
     * @return MirroredTimebox[]
     */
    public function retrieveMilestonesLinkedTo(int $program_increment_id): array
    {
        $linked_artifacts_ids = $this->mirrored_milestones_dao->getMirroredTimeboxes(
            $program_increment_id,
            TimeboxArtifactLinkType::ART_LINK_SHORT_NAME
        );

        $link_to = [];
        foreach ($linked_artifacts_ids as $link) {
            $link_to[] = new MirroredTimebox($link['id']);
        }

        return $link_to;
    }
}
