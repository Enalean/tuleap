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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\ArtifactsLinksSearch;

class FeaturePlanChangeTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testGetUserStoriesLinkedToFeature(): void
    {
        $feature_to_links = [
            ['artifact_id' => "123"],
            ['artifact_id' => "456"]
        ];

        $feature_plan_change = FeaturePlanChange::fromRaw($this->getStubArtifactsLinkSearch(), $feature_to_links, 1);

        self::assertEquals(789, $feature_plan_change->user_stories[0]->id);
        self::assertEquals(101, $feature_plan_change->user_stories[0]->project_id);
        self::assertEquals(910, $feature_plan_change->user_stories[1]->id);
        self::assertEquals(156, $feature_plan_change->user_stories[1]->project_id);
    }

    private function getStubArtifactsLinkSearch(): ArtifactsLinksSearch
    {
        return new class () implements ArtifactsLinksSearch {
            public function getArtifactsLinkedToId(int $artifact_id, int $program_increment_id): array
            {
                if ($artifact_id === 123) {
                    return [['id' => 789, 'project_id' => 101]];
                }
                if ($artifact_id === 456) {
                    return [['id' => 910, 'project_id' => 156]];
                }
                return [];
            }
        };
    }
}
