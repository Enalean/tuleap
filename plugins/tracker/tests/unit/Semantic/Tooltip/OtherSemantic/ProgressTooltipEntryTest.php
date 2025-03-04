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

use TemplateRendererFactory;
use Tuleap\Templating\TemplateCache;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Semantic\Progress\IComputeProgression;
use Tuleap\Tracker\Semantic\Progress\InvalidMethod;
use Tuleap\Tracker\Semantic\Progress\MethodNotConfigured;
use Tuleap\Tracker\Semantic\Progress\ProgressionResult;
use Tuleap\Tracker\Semantic\Progress\SemanticProgress;
use Tuleap\Tracker\Semantic\Progress\SemanticProgressBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgressTooltipEntryTest extends TestCase
{
    public function testEmptyEntryWhenSemanticIsNotConfigured(): void
    {
        $semantic_progress_builder = $this->createMock(SemanticProgressBuilder::class);
        $semantic_progress_builder->method('getSemantic')->willReturn(new SemanticProgress(
            TrackerTestBuilder::aTracker()->build(),
            new MethodNotConfigured()
        ));

        $template_cache = $this->createMock(TemplateCache::class);
        $template_cache->method('getPath')->willReturn(null);

        $template_factory = new TemplateRendererFactory($template_cache);

        $entry = new ProgressTooltipEntry($semantic_progress_builder, $template_factory);

        $artifact = ArtifactTestBuilder::anArtifact(101)->build();
        $user     = UserTestBuilder::buildWithDefaults();

        self::assertEmpty($entry->fetchTooltipEntry($artifact, $user));
    }

    public function testInvalidConfiguration(): void
    {
        $semantic_progress_builder = $this->createMock(SemanticProgressBuilder::class);
        $semantic_progress_builder->method('getSemantic')->willReturn(new SemanticProgress(
            TrackerTestBuilder::aTracker()->build(),
            new InvalidMethod('Invalid configuration')
        ));

        $template_cache = $this->createMock(TemplateCache::class);
        $template_cache->method('getPath')->willReturn(null);

        $template_factory = new TemplateRendererFactory($template_cache);

        $entry = new ProgressTooltipEntry($semantic_progress_builder, $template_factory);

        $artifact = ArtifactTestBuilder::anArtifact(101)->build();
        $user     = UserTestBuilder::buildWithDefaults();

        $tooltip_entry = $entry->fetchTooltipEntry($artifact, $user);
        self::assertStringContainsString('crossref-tooltip-value-error', $tooltip_entry);
        self::assertStringContainsString('Invalid configuration', $tooltip_entry);
    }

    public function testPercentage(): void
    {
        $semantic_progress_builder = $this->createMock(SemanticProgressBuilder::class);

        $method = $this->createMock(IComputeProgression::class);
        $method->method('isConfigured')->willReturn(true);
        $method->method('computeProgression')->willReturn(new ProgressionResult(0.75, ''));
        $semantic_progress_builder->method('getSemantic')->willReturn(new SemanticProgress(
            TrackerTestBuilder::aTracker()->build(),
            $method
        ));

        $template_cache = $this->createMock(TemplateCache::class);
        $template_cache->method('getPath')->willReturn(null);

        $template_factory = new TemplateRendererFactory($template_cache);

        $entry = new ProgressTooltipEntry($semantic_progress_builder, $template_factory);

        $artifact = ArtifactTestBuilder::anArtifact(101)->build();
        $user     = UserTestBuilder::buildWithDefaults();

        $tooltip_entry = $entry->fetchTooltipEntry($artifact, $user);
        self::assertStringNotContainsString('crossref-tooltip-value-error', $tooltip_entry);
        self::assertStringContainsString('75%', $tooltip_entry);
    }

    public function testPercentageIsGreaterOrEqualTo0(): void
    {
        $semantic_progress_builder = $this->createMock(SemanticProgressBuilder::class);

        $method = $this->createMock(IComputeProgression::class);
        $method->method('isConfigured')->willReturn(true);
        $method->method('computeProgression')->willReturn(new ProgressionResult(-0.75, ''));
        $semantic_progress_builder->method('getSemantic')->willReturn(new SemanticProgress(
            TrackerTestBuilder::aTracker()->build(),
            $method
        ));

        $template_cache = $this->createMock(TemplateCache::class);
        $template_cache->method('getPath')->willReturn(null);

        $template_factory = new TemplateRendererFactory($template_cache);

        $entry = new ProgressTooltipEntry($semantic_progress_builder, $template_factory);

        $artifact = ArtifactTestBuilder::anArtifact(101)->build();
        $user     = UserTestBuilder::buildWithDefaults();

        $tooltip_entry = $entry->fetchTooltipEntry($artifact, $user);
        self::assertStringNotContainsString('crossref-tooltip-value-error', $tooltip_entry);
        self::assertStringContainsString('0%', $tooltip_entry);
    }

    public function testPercentageIsLesserOrEqualTo1(): void
    {
        $semantic_progress_builder = $this->createMock(SemanticProgressBuilder::class);

        $method = $this->createMock(IComputeProgression::class);
        $method->method('isConfigured')->willReturn(true);
        $method->method('computeProgression')->willReturn(new ProgressionResult(2, ''));
        $semantic_progress_builder->method('getSemantic')->willReturn(new SemanticProgress(
            TrackerTestBuilder::aTracker()->build(),
            $method
        ));

        $template_cache = $this->createMock(TemplateCache::class);
        $template_cache->method('getPath')->willReturn(null);

        $template_factory = new TemplateRendererFactory($template_cache);

        $entry = new ProgressTooltipEntry($semantic_progress_builder, $template_factory);

        $artifact = ArtifactTestBuilder::anArtifact(101)->build();
        $user     = UserTestBuilder::buildWithDefaults();

        $tooltip_entry = $entry->fetchTooltipEntry($artifact, $user);
        self::assertStringNotContainsString('crossref-tooltip-value-error', $tooltip_entry);
        self::assertStringContainsString('100%', $tooltip_entry);
    }
}
