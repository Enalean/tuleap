<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning\Admin;

use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class UpdateRequestValidatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ORIGINAL_MILESTONE_TRACKER_ID = 54;
    private const NEW_MILESTONE_TRACKER_ID      = 97;
    /** @var int[] */
    private array $unavailable_planning_tracker_ids;
    private \Planning $original_planning;
    private ?ModificationBan $modification_ban;

    protected function setUp(): void
    {
        $this->unavailable_planning_tracker_ids = [];
        $this->modification_ban                 = null;

        $milestone_tracker       = TrackerTestBuilder::aTracker()->withId(self::ORIGINAL_MILESTONE_TRACKER_ID)->build();
        $this->original_planning = PlanningBuilder::aPlanning(101)
            ->withMilestoneTracker($milestone_tracker)
            ->build();
    }

    private function validate(\Codendi_Request $request): ?\PlanningParameters
    {
        $validator = new UpdateRequestValidator();

        return $validator->getValidatedPlanning(
            $this->original_planning,
            $request,
            $this->unavailable_planning_tracker_ids,
            $this->modification_ban
        );
    }

    /**
     * @dataProvider dataProviderInvalidRequest
     * @param array | null $request_parameters
     */
    public function testItRejectsTheRequestWhenItIsInvalid($request_parameters): void
    {
        $request = $this->buildRequest($request_parameters);

        $this->assertNull($this->validate($request));
    }

    public static function dataProviderInvalidRequest(): array
    {
        return [
            'No planning parameter'                => [null],
            'Missing name'                         => [['not_name' => 'Irrelevant']],
            'Missing backlog tracker ids'          => [[\PlanningParameters::NAME => 'Release Planning']],
            'Backlog tracker ids are not integers' => [[
                \PlanningParameters::NAME                => 'Release Planning',
                \PlanningParameters::BACKLOG_TRACKER_IDS => ['bad', 'bad'],
            ],
            ],
            'Missing planning tracker id'          => [[
                \PlanningParameters::NAME                => 'Release Planning',
                \PlanningParameters::BACKLOG_TRACKER_IDS => [10, 26],
            ],
            ],
            'Planning tracker id is not integer'   => [[
                \PlanningParameters::NAME                => 'Release Planning',
                \PlanningParameters::BACKLOG_TRACKER_IDS => [10, 26],
                \PlanningParameters::PLANNING_TRACKER_ID => 'bad',
            ],
            ],
        ];
    }

    public function testWhenPlanningTrackerModificationIsBannedItForcesItToOriginal(): void
    {
        $request                = $this->buildRequest(
            [
                \PlanningParameters::NAME                => 'Release Planning',
                \PlanningParameters::BACKLOG_TRACKER_IDS => [10, 26],
            ]
        );
        $this->modification_ban = new class implements ModificationBan {
            public function getMessage(): string
            {
                return 'Cannot modify planning tracker';
            }
        };

        $validated_updated_planning = $this->validate($request);
        $this->assertNotNull($validated_updated_planning);
        $this->assertSame((string) self::ORIGINAL_MILESTONE_TRACKER_ID, $validated_updated_planning->planning_tracker_id);
    }

    public function testItRejectsTheRequestWhenPlanningTrackerIsUnavailable(): void
    {
        $request                                = $this->buildRequest(
            [
                \PlanningParameters::NAME                => 'Release Planning',
                \PlanningParameters::BACKLOG_TRACKER_IDS => [10, 26],
                \PlanningParameters::PLANNING_TRACKER_ID => self::NEW_MILESTONE_TRACKER_ID,
            ]
        );
        $this->unavailable_planning_tracker_ids = [self::NEW_MILESTONE_TRACKER_ID];

        $this->assertNull($this->validate($request));
    }

    public function testItReturnsValidatedPlanningParametersWhenPlanningTrackerIsAvailable(): void
    {
        $request                                = $this->buildRequest(
            [
                \PlanningParameters::NAME                => 'Release Planning',
                \PlanningParameters::BACKLOG_TITLE       => 'Product Backlog',
                \PlanningParameters::PLANNING_TITLE      => 'Release Plan',
                \PlanningParameters::BACKLOG_TRACKER_IDS => [10, 26],
                \PlanningParameters::PLANNING_TRACKER_ID => self::NEW_MILESTONE_TRACKER_ID,
            ]
        );
        $this->unavailable_planning_tracker_ids = [189];

        $validated_updated_planning = $this->validate($request);
        $this->assertNotNull($validated_updated_planning);
        $this->assertSame('Release Planning', $validated_updated_planning->name);
        $this->assertContains(10, $validated_updated_planning->backlog_tracker_ids);
        $this->assertContains(26, $validated_updated_planning->backlog_tracker_ids);
        $this->assertSame(self::NEW_MILESTONE_TRACKER_ID, $validated_updated_planning->planning_tracker_id);
        $this->assertSame('Product Backlog', $validated_updated_planning->backlog_title);
        $this->assertSame('Release Plan', $validated_updated_planning->plan_title);
    }

    public function testItReturnsValidatedPlanningParametersWhenPlanningTrackerDidNotChange(): void
    {
        $request                                = $this->buildRequest(
            [
                \PlanningParameters::NAME                => 'Release Planning',
                \PlanningParameters::BACKLOG_TRACKER_IDS => [10, 26],
                \PlanningParameters::PLANNING_TRACKER_ID => self::NEW_MILESTONE_TRACKER_ID,
            ]
        );
        $milestone_tracker                      = TrackerTestBuilder::aTracker()->withId(self::NEW_MILESTONE_TRACKER_ID)->build();
        $this->original_planning                = PlanningBuilder::aPlanning(101)
            ->withMilestoneTracker($milestone_tracker)
            ->build();
        $this->unavailable_planning_tracker_ids = [self::NEW_MILESTONE_TRACKER_ID];
        $this->assertNotNull($this->validate($request));
    }

    /**
     * @param array | null $planning_request
     */
    private function buildRequest($planning_request): \Codendi_Request
    {
        return new \Codendi_Request(['planning' => $planning_request]);
    }
}
