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

namespace Tuleap\Tracker\Semantic\Tooltip\OtherSemantic;

use Psr\Log\NullLogger;
use TemplateRendererFactory;
use Tuleap\Date\DatePeriodWithoutWeekEnd;
use Tuleap\Templating\TemplateCache;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeNotConfigured;
use Tuleap\Tracker\Semantic\TimeframeConfigInvalid;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementDateFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Semantic\Timeframe\IComputeTimeframesStub;

final class TimeframeTooltipEntryTest extends TestCase
{
    public function testEmptyEntryWhenSemanticIsNotConfigured(): void
    {
        $semantic_timeframe_builder = $this->createMock(SemanticTimeframeBuilder::class);
        $semantic_timeframe_builder->method('getSemantic')->willReturn(new SemanticTimeframe(
            TrackerTestBuilder::aTracker()->build(),
            new TimeframeNotConfigured()
        ));

        $template_cache = $this->createMock(TemplateCache::class);
        $template_cache->method('getPath')->willReturn(null);

        $template_factory = new TemplateRendererFactory($template_cache);

        $entry = new TimeframeTooltipEntry($semantic_timeframe_builder, $template_factory, new NullLogger());

        $artifact = ArtifactTestBuilder::anArtifact(101)->build();
        $user     = UserTestBuilder::buildWithDefaults();

        self::assertEmpty($entry->fetchTooltipEntry($artifact, $user));
    }

    public function testInvalidConfiguration(): void
    {
        $semantic_timeframe_builder = $this->createMock(SemanticTimeframeBuilder::class);
        $semantic_timeframe_builder->method('getSemantic')->willReturn(new SemanticTimeframe(
            TrackerTestBuilder::aTracker()->build(),
            new TimeframeConfigInvalid()
        ));

        $template_cache = $this->createMock(TemplateCache::class);
        $template_cache->method('getPath')->willReturn(null);

        $template_factory = new TemplateRendererFactory($template_cache);

        $entry = new TimeframeTooltipEntry($semantic_timeframe_builder, $template_factory, new NullLogger());

        $artifact = ArtifactTestBuilder::anArtifact(101)->build();
        $user     = UserTestBuilder::buildWithDefaults();

        self::assertEmpty($entry->fetchTooltipEntry($artifact, $user));
    }

    public function testTimeframe(): void
    {
        $user  = UserTestBuilder::buildWithDefaults();
        $start = TrackerFormElementDateFieldBuilder::aDateField(1)
            ->withReadPermission($user, true)
            ->build();
        $end   = TrackerFormElementDateFieldBuilder::aDateField(2)
            ->withReadPermission($user, true)
            ->build();

        $semantic_timeframe_builder = $this->createMock(SemanticTimeframeBuilder::class);
        $semantic_timeframe_builder->method('getSemantic')->willReturn(new SemanticTimeframe(
            TrackerTestBuilder::aTracker()->build(),
            IComputeTimeframesStub::fromStartAndEndDates(
                DatePeriodWithoutWeekEnd::buildFromEndDate(1234567890, 1324567890, new NullLogger()),
                $start,
                $end,
            )
        ));

        $template_cache = $this->createMock(TemplateCache::class);
        $template_cache->method('getPath')->willReturn(null);

        $template_factory = new TemplateRendererFactory($template_cache);

        $entry = new TimeframeTooltipEntry($semantic_timeframe_builder, $template_factory, new NullLogger());

        $changeset = ChangesetTestBuilder::aChangeset('1')->build();
        $artifact  = ArtifactTestBuilder::anArtifact(101)
            ->withChangesets($changeset)
            ->build();

        $tooltip_entry = $entry->fetchTooltipEntry($artifact, $user);
        self::assertStringContainsString('Start date', $tooltip_entry);
        self::assertStringContainsString('February 14, 2009', $tooltip_entry);
        self::assertStringContainsString('End date', $tooltip_entry);
        self::assertStringContainsString('December 22, 2011', $tooltip_entry);
    }

    public function testNoTimeframeWhenUserCannotReadStartField(): void
    {
        $start = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $start->method('userCanRead')->willReturn(false);
        $end = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $end->method('userCanRead')->willReturn(true);

        $semantic_timeframe_builder = $this->createMock(SemanticTimeframeBuilder::class);
        $semantic_timeframe_builder->method('getSemantic')->willReturn(new SemanticTimeframe(
            TrackerTestBuilder::aTracker()->build(),
            IComputeTimeframesStub::fromStartAndEndDates(
                DatePeriodWithoutWeekEnd::buildFromEndDate(1234567890, 1324567890, new NullLogger()),
                $start,
                $end,
            )
        ));

        $template_cache = $this->createMock(TemplateCache::class);
        $template_cache->method('getPath')->willReturn(null);

        $template_factory = new TemplateRendererFactory($template_cache);

        $entry = new TimeframeTooltipEntry($semantic_timeframe_builder, $template_factory, new NullLogger());

        $artifact = ArtifactTestBuilder::anArtifact(101)->build();
        $user     = UserTestBuilder::buildWithDefaults();

        self::assertEmpty($entry->fetchTooltipEntry($artifact, $user));
    }

    public function testNoTimeframeWhenUserCannotReadEndField(): void
    {
        $start = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $start->method('userCanRead')->willReturn(true);
        $end = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $end->method('userCanRead')->willReturn(false);

        $semantic_timeframe_builder = $this->createMock(SemanticTimeframeBuilder::class);
        $semantic_timeframe_builder->method('getSemantic')->willReturn(new SemanticTimeframe(
            TrackerTestBuilder::aTracker()->build(),
            IComputeTimeframesStub::fromStartAndEndDates(
                DatePeriodWithoutWeekEnd::buildFromEndDate(1234567890, 1324567890, new NullLogger()),
                $start,
                $end,
            )
        ));

        $template_cache = $this->createMock(TemplateCache::class);
        $template_cache->method('getPath')->willReturn(null);

        $template_factory = new TemplateRendererFactory($template_cache);

        $entry = new TimeframeTooltipEntry($semantic_timeframe_builder, $template_factory, new NullLogger());

        $artifact = ArtifactTestBuilder::anArtifact(101)->build();
        $user     = UserTestBuilder::buildWithDefaults();

        self::assertEmpty($entry->fetchTooltipEntry($artifact, $user));
    }

    public function testNoTimeframeWhenUserCannotReadDurationField(): void
    {
        $start = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $start->method('userCanRead')->willReturn(true);
        $duration = $this->createMock(\Tracker_FormElement_Field_Numeric::class);
        $duration->method('userCanRead')->willReturn(false);

        $semantic_timeframe_builder = $this->createMock(SemanticTimeframeBuilder::class);
        $semantic_timeframe_builder->method('getSemantic')->willReturn(new SemanticTimeframe(
            TrackerTestBuilder::aTracker()->build(),
            IComputeTimeframesStub::fromStartAndDuration(
                DatePeriodWithoutWeekEnd::buildFromEndDate(1234567890, 1324567890, new NullLogger()),
                $start,
                $duration,
            )
        ));

        $template_cache = $this->createMock(TemplateCache::class);
        $template_cache->method('getPath')->willReturn(null);

        $template_factory = new TemplateRendererFactory($template_cache);

        $entry = new TimeframeTooltipEntry($semantic_timeframe_builder, $template_factory, new NullLogger());

        $artifact = ArtifactTestBuilder::anArtifact(101)->build();
        $user     = UserTestBuilder::buildWithDefaults();

        self::assertEmpty($entry->fetchTooltipEntry($artifact, $user));
    }

    public function testUndefinedStart(): void
    {
        $user  = UserTestBuilder::buildWithDefaults();
        $start = TrackerFormElementDateFieldBuilder::aDateField(1)
            ->withReadPermission($user, true)
            ->build();
        $end   = TrackerFormElementDateFieldBuilder::aDateField(2)
            ->withReadPermission($user, true)
            ->build();

        $semantic_timeframe_builder = $this->createMock(SemanticTimeframeBuilder::class);
        $semantic_timeframe_builder->method('getSemantic')->willReturn(new SemanticTimeframe(
            TrackerTestBuilder::aTracker()->build(),
            IComputeTimeframesStub::fromStartAndEndDates(
                DatePeriodWithoutWeekEnd::buildFromEndDate(null, 1324567890, new NullLogger()),
                $start,
                $end,
            )
        ));

        $template_cache = $this->createMock(TemplateCache::class);
        $template_cache->method('getPath')->willReturn(null);

        $template_factory = new TemplateRendererFactory($template_cache);

        $entry = new TimeframeTooltipEntry($semantic_timeframe_builder, $template_factory, new NullLogger());

        $changeset = ChangesetTestBuilder::aChangeset('1')->build();
        $artifact  = ArtifactTestBuilder::anArtifact(101)
            ->withChangesets($changeset)
            ->build();

        $tooltip_entry = $entry->fetchTooltipEntry($artifact, $user);
        self::assertStringContainsString('Start date', $tooltip_entry);
        self::assertStringContainsString('Undefined', $tooltip_entry);
        self::assertStringContainsString('End date', $tooltip_entry);
        self::assertStringContainsString('December 22, 2011', $tooltip_entry);
    }

    public function testUndefinedEnd(): void
    {
        $user  = UserTestBuilder::buildWithDefaults();
        $start = TrackerFormElementDateFieldBuilder::aDateField(1)
            ->withReadPermission($user, true)
            ->build();
        $end   = TrackerFormElementDateFieldBuilder::aDateField(2)
            ->withReadPermission($user, true)
            ->build();

        $semantic_timeframe_builder = $this->createMock(SemanticTimeframeBuilder::class);
        $semantic_timeframe_builder->method('getSemantic')->willReturn(new SemanticTimeframe(
            TrackerTestBuilder::aTracker()->build(),
            IComputeTimeframesStub::fromStartAndEndDates(
                DatePeriodWithoutWeekEnd::buildFromEndDate(1234567890, null, new NullLogger()),
                $start,
                $end,
            )
        ));

        $template_cache = $this->createMock(TemplateCache::class);
        $template_cache->method('getPath')->willReturn(null);

        $template_factory = new TemplateRendererFactory($template_cache);

        $entry = new TimeframeTooltipEntry($semantic_timeframe_builder, $template_factory, new NullLogger());

        $changeset = ChangesetTestBuilder::aChangeset('1')->build();
        $artifact  = ArtifactTestBuilder::anArtifact(101)
            ->withChangesets($changeset)
            ->build();

        $tooltip_entry = $entry->fetchTooltipEntry($artifact, $user);
        self::assertStringContainsString('Start date', $tooltip_entry);
        self::assertStringContainsString('February 14, 2009', $tooltip_entry);
        self::assertStringContainsString('End date', $tooltip_entry);
        self::assertStringContainsString('Undefined', $tooltip_entry);
    }

    public function testNoTimeframeWhenNoStartAndNoEnd(): void
    {
        $user  = UserTestBuilder::buildWithDefaults();
        $start = TrackerFormElementDateFieldBuilder::aDateField(1)
            ->withReadPermission($user, true)
            ->build();
        $end   = TrackerFormElementDateFieldBuilder::aDateField(2)
            ->withReadPermission($user, true)
            ->build();

        $semantic_timeframe_builder = $this->createMock(SemanticTimeframeBuilder::class);
        $semantic_timeframe_builder->method('getSemantic')->willReturn(new SemanticTimeframe(
            TrackerTestBuilder::aTracker()->build(),
            IComputeTimeframesStub::fromStartAndEndDates(
                DatePeriodWithoutWeekEnd::buildWithoutAnyDates(),
                $start,
                $end,
            )
        ));

        $template_cache = $this->createMock(TemplateCache::class);
        $template_cache->method('getPath')->willReturn(null);

        $template_factory = new TemplateRendererFactory($template_cache);

        $entry = new TimeframeTooltipEntry($semantic_timeframe_builder, $template_factory, new NullLogger());

        $changeset = ChangesetTestBuilder::aChangeset('1')->build();
        $artifact  = ArtifactTestBuilder::anArtifact(101)
            ->withChangesets($changeset)
            ->build();

        self::assertEmpty($entry->fetchTooltipEntry($artifact, $user));
    }

    public function testWarningWhenEndDateIsBeforeStartDate(): void
    {
        $user  = UserTestBuilder::buildWithDefaults();
        $start = TrackerFormElementDateFieldBuilder::aDateField(1)
            ->withReadPermission($user, true)
            ->build();
        $end   = TrackerFormElementDateFieldBuilder::aDateField(2)
            ->withReadPermission($user, true)
            ->build();

        $semantic_timeframe_builder = $this->createMock(SemanticTimeframeBuilder::class);
        $semantic_timeframe_builder->method('getSemantic')->willReturn(new SemanticTimeframe(
            TrackerTestBuilder::aTracker()->build(),
            IComputeTimeframesStub::fromStartAndEndDates(
                DatePeriodWithoutWeekEnd::buildFromEndDate(1324567890, 1234567890, new NullLogger()),
                $start,
                $end,
            )
        ));

        $template_cache = $this->createMock(TemplateCache::class);
        $template_cache->method('getPath')->willReturn(null);

        $template_factory = new TemplateRendererFactory($template_cache);

        $entry = new TimeframeTooltipEntry($semantic_timeframe_builder, $template_factory, new NullLogger());

        $changeset = ChangesetTestBuilder::aChangeset('1')->build();
        $artifact  = ArtifactTestBuilder::anArtifact(101)
            ->withChangesets($changeset)
            ->build();

        $tooltip_entry = $entry->fetchTooltipEntry($artifact, $user);
        self::assertStringContainsString('Start date', $tooltip_entry);
        self::assertStringContainsString('December 22, 2011', $tooltip_entry);
        self::assertStringContainsString('End date', $tooltip_entry);
        self::assertStringContainsString('February 14, 2009', $tooltip_entry);
        self::assertStringContainsString('End date is lesser than start date!', $tooltip_entry);
    }
}
