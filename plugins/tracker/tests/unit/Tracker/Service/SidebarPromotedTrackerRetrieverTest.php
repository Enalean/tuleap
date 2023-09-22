<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Service;

use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrievePromotedTrackersStub;

final class SidebarPromotedTrackerRetrieverTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testEmptyWhenNoFeatureFlag(): void
    {
        $user    = UserTestBuilder::buildWithDefaults();
        $project = ProjectTestBuilder::aProject()->build();

        $retriever = new SidebarPromotedTrackerRetriever(
            RetrievePromotedTrackersStub::withTrackers(TrackerTestBuilder::aTracker()->build())
        );

        self::assertEmpty($retriever->getPromotedItemPresenters($user, $project, 'whatever'));
    }

    public function testEmptyWhenFeatureFlagIs0(): void
    {
        \ForgeConfig::setFeatureFlag(SidebarPromotedTrackerRetriever::FEATURE_FLAG, '0');

        $user    = UserTestBuilder::buildWithDefaults();
        $project = ProjectTestBuilder::aProject()->build();

        $retriever = new SidebarPromotedTrackerRetriever(
            RetrievePromotedTrackersStub::withTrackers(TrackerTestBuilder::aTracker()->build())
        );

        self::assertEmpty($retriever->getPromotedItemPresenters($user, $project, 'whatever'));
    }

    public function testEmptyWhenFeatureFlagIsNotForCurrentProject(): void
    {
        \ForgeConfig::setFeatureFlag(SidebarPromotedTrackerRetriever::FEATURE_FLAG, '121');

        $user    = UserTestBuilder::buildWithDefaults();
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $retriever = new SidebarPromotedTrackerRetriever(
            RetrievePromotedTrackersStub::withTrackers(TrackerTestBuilder::aTracker()->build())
        );

        self::assertEmpty($retriever->getPromotedItemPresenters($user, $project, 'whatever'));
    }

    public function testPromotedTrackersWhenProjectIsPartOfFeatureFlag(): void
    {
        \ForgeConfig::setFeatureFlag(SidebarPromotedTrackerRetriever::FEATURE_FLAG, '101');

        $user    = UserTestBuilder::buildWithDefaults();
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $retriever = new SidebarPromotedTrackerRetriever(
            RetrievePromotedTrackersStub::withTrackers(
                TrackerTestBuilder::aTracker()->withName('Bugs')->build(),
                TrackerTestBuilder::aTracker()->withName('Requests')->build(),
            )
        );

        self::assertCount(
            2,
            $retriever->getPromotedItemPresenters($user, $project, 'whatever'),
        );
    }

    public function testPromotedTrackersWhenFeatureFlagIsForEveryProjects(): void
    {
        \ForgeConfig::setFeatureFlag(SidebarPromotedTrackerRetriever::FEATURE_FLAG, '1');

        $user    = UserTestBuilder::buildWithDefaults();
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $retriever = new SidebarPromotedTrackerRetriever(
            RetrievePromotedTrackersStub::withTrackers(
                TrackerTestBuilder::aTracker()->withName('Bugs')->build(),
                TrackerTestBuilder::aTracker()->withName('Requests')->build(),
            )
        );

        self::assertCount(
            2,
            $retriever->getPromotedItemPresenters($user, $project, 'whatever'),
        );
    }

    public function testPromotedTrackerIsMarkedAsActiveIfWeDetectThatItIsTheCurrentOne(): void
    {
        \ForgeConfig::setFeatureFlag(SidebarPromotedTrackerRetriever::FEATURE_FLAG, '1');

        $user    = UserTestBuilder::buildWithDefaults();
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $bugs      = TrackerTestBuilder::aTracker()->withId(1001)->withName('Bugs')->build();
        $requests  = TrackerTestBuilder::aTracker()->withId(1002)->withName('Requests')->build();
        $retriever = new SidebarPromotedTrackerRetriever(
            RetrievePromotedTrackersStub::withTrackers(
                $bugs,
                $requests,
            )
        );

        $promoted_item_presenters = $retriever->getPromotedItemPresenters($user, $project, $requests->getPromotedTrackerId());
        self::assertCount(2, $promoted_item_presenters);
        self::assertSame($bugs->getName(), $promoted_item_presenters[0]->label);
        self::assertFalse($promoted_item_presenters[0]->is_active);
        self::assertSame($requests->getName(), $promoted_item_presenters[1]->label);
        self::assertTrue($promoted_item_presenters[1]->is_active);
    }

    public function testEmptyPromotedTrackers(): void
    {
        \ForgeConfig::setFeatureFlag(SidebarPromotedTrackerRetriever::FEATURE_FLAG, '1');

        $user    = UserTestBuilder::buildWithDefaults();
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $retriever = new SidebarPromotedTrackerRetriever(
            RetrievePromotedTrackersStub::withoutTrackers()
        );

        self::assertEmpty(
            $retriever->getPromotedItemPresenters($user, $project, 'whatever'),
        );
    }
}
