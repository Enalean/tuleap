<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Layout\ProjectSidebar;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\BuildVersion\FlavorFinder;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Layout\Logo\IDetectIfLogoIsCustomized;
use Tuleap\Layout\ProjectSidebarToolsBuilder;
use Tuleap\Project\Banner\BannerRetriever;
use Tuleap\Project\Flags\ProjectFlagsBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\VerifyUserCanAccessProjectAdministrationStub;
use Tuleap\User\CurrentUserWithLoggedInInformation;

final class ProjectSidebarConfigRepresentationTest extends TestCase
{
    public function testBuildsRepresentation(): void
    {
        $project       = ProjectTestBuilder::aProject()->build();
        $base_language = $this->createStub(\BaseLanguage::class);
        $base_language->method('hasText')->willReturn(false);
        $user = $this->createStub(\PFUser::class);
        $user->method('getLanguage')->willReturn($base_language);
        $user->method('isAnonymous')->willReturn(false);
        $banner_retriever = $this->createStub(BannerRetriever::class);
        $banner_retriever->method('getBannerForProject')->willReturn(null);
        $project_flags_builder = $this->createStub(ProjectFlagsBuilder::class);
        $project_flags_builder->method('buildProjectFlags')->willReturn([]);
        $project_sidebar_tools_builder = $this->createStub(ProjectSidebarToolsBuilder::class);
        $project_sidebar_tools_builder->method('getSidebarTools')->willReturn(
            (static function (): \Generator {
                yield from [];
            })()
        );

        $representation = ProjectSidebarConfigRepresentation::build(
            $project,
            CurrentUserWithLoggedInInformation::fromLoggedInUser($user),
            $banner_retriever,
            $project_flags_builder,
            new class implements EventDispatcherInterface
            {
                public function dispatch(object $event): object
                {
                    return $event;
                }
            },
            VerifyUserCanAccessProjectAdministrationStub::withPermittedAccess(),
            new class implements FlavorFinder {
                public function isEnterprise(): bool
                {
                    return false;
                }
            },
            new class implements IDetectIfLogoIsCustomized {
                public function isLegacyOrganizationLogoCustomized(): bool
                {
                    return false;
                }

                public function isSvgOrganizationLogoCustomized(): bool
                {
                    return false;
                }
            },
            $this->createStub(GlyphFinder::class),
            $project_sidebar_tools_builder,
            '',
            null,
        );

        self::assertNotNull($representation);
    }
}
