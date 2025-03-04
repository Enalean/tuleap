<?php
/**
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Admin\ArtifactDeletion;

use Tracker;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\TestLayout;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Admin\ArtifactsDeletion\ArtifactsConfirmDeletionInTrackerAdminUrlBuilder;
use Tuleap\Tracker\Admin\ArtifactsDeletion\ArtifactsDeletionInTrackerAdminController;
use Tuleap\Tracker\Admin\ArtifactsDeletion\ArtifactsDeletionInTrackerAdminPresenter;
use Tuleap\Tracker\Test\Stub\ConfigurationArtifactsDeletionStub;
use Tuleap\Tracker\Test\Stub\RetrieveTrackerStub;
use Tuleap\Tracker\Test\Stub\RetrieveUserDeletionForLastDayStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactsDeletionInTrackerAdminControllerTest extends TestCase
{
    private const TRACKER_ID = 10;

    public function tearDown(): void
    {
        unset($GLOBALS['_SESSION']);
    }

    public function testItThrowsNotFoundExceptionWhenTrackerIsNotFound(): void
    {
        $controller = new ArtifactsDeletionInTrackerAdminController(
            RetrieveTrackerStub::withoutTracker(),
            $this->createMock(\TrackerManager::class),
            $this->createMock(\TemplateRenderer::class),
            ConfigurationArtifactsDeletionStub::withLimit(0),
            RetrieveUserDeletionForLastDayStub::withAlreadyDoneDeletions(0),
        );

        $this->expectException(NotFoundException::class);

        $controller->process(
            HTTPRequestBuilder::get()->build(),
            new TestLayout(new LayoutInspector()),
            ['tracker_id' => self::TRACKER_ID]
        );
    }

    public function testItThrowsNotFoundExceptionWhenUserIsNotTrackerAdmin(): void
    {
        $user    = UserTestBuilder::anActiveUser()->build();
        $tracker = $this->createMock(Tracker::class);
        $tracker->expects(self::once())->method('getId')->willReturn(self::TRACKER_ID);
        $tracker->expects(self::once())->method('userIsAdmin')->with($user)->willReturn(false);

        $controller = new ArtifactsDeletionInTrackerAdminController(
            RetrieveTrackerStub::withTrackers($tracker),
            $this->createMock(\TrackerManager::class),
            $this->createMock(\TemplateRenderer::class),
            ConfigurationArtifactsDeletionStub::withLimit(0),
            RetrieveUserDeletionForLastDayStub::withAlreadyDoneDeletions(0),
        );

        $this->expectException(NotFoundException::class);

        $controller->process(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            new TestLayout(new LayoutInspector()),
            ['tracker_id' => self::TRACKER_ID]
        );
    }

    public function testHappyPath(): void
    {
        $user    = UserTestBuilder::anActiveUser()->build();
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(self::TRACKER_ID);
        $tracker->expects(self::once())->method('userIsAdmin')->with($user)->willReturn(true);
        $tracker->expects(self::once())->method('displayAdminItemHeaderBurningParrot');
        $tracker->expects(self::once())->method('displayFooter');

        $url_to_deletion_confirmation = ArtifactsConfirmDeletionInTrackerAdminUrlBuilder::fromTracker($tracker);
        $deletion_limit               = 10;
        $deletion_count               = 2;

        $template_renderer = $this->createMock(\TemplateRenderer::class);
        $template_renderer->expects(self::once())->method('renderToPage')->with(
            'admin-artifacts-deletion',
            new ArtifactsDeletionInTrackerAdminPresenter(
                $url_to_deletion_confirmation->getCSRFSynchronizerToken(),
                $url_to_deletion_confirmation->getUrl(),
                $deletion_limit,
                $deletion_count,
            )
        );

        $controller = new ArtifactsDeletionInTrackerAdminController(
            RetrieveTrackerStub::withTrackers($tracker),
            $this->createMock(\TrackerManager::class),
            $template_renderer,
            ConfigurationArtifactsDeletionStub::withLimit($deletion_limit),
            RetrieveUserDeletionForLastDayStub::withAlreadyDoneDeletions($deletion_count),
        );

        $controller->process(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            new TestLayout(new LayoutInspector()),
            ['tracker_id' => self::TRACKER_ID]
        );
    }
}
