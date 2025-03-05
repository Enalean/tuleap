<?php
/**
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

declare(strict_types=1);

use Tuleap\ProgramManagement\Adapter\Program\Backlog\TimeboxArtifactLinkPresenter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxArtifactLinkType;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class program_managementPluginTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testProvidesArtLinkTypes(): void
    {
        $plugin = new program_managementPlugin(1);
        $types  = [];
        $params = ['types' => &$types];
        $plugin->getArtifactLinkTypes($params);

        self::assertEquals([new TimeboxArtifactLinkPresenter()], $types);
    }

    public function testProvidesTypePresenterWhenTheTypeIsExposedByThePlugin(): void
    {
        $plugin    = new program_managementPlugin(1);
        $presenter = null;
        $params    = ['shortname' => TimeboxArtifactLinkType::ART_LINK_SHORT_NAME, 'presenter' => &$presenter];
        $plugin->getTypePresenter($params);

        self::assertEquals(new TimeboxArtifactLinkPresenter(), $presenter);
    }

    public function testDoesNotProvideTypePresenterWhenTheTypeIsNotExposedByThePlugin(): void
    {
        $plugin    = new program_managementPlugin(1);
        $presenter = null;
        $params    = ['shortname' => 'something', 'presenter' => &$presenter];
        $plugin->getTypePresenter($params);

        self::assertNull($presenter);
    }

    public function testExposesSystemArtifactLinkType(): void
    {
        $plugin = new program_managementPlugin(1);
        $types  = [];
        $params = ['types' => &$types];
        $plugin->trackerAddSystemTypes($params);

        self::assertEquals([TimeboxArtifactLinkType::ART_LINK_SHORT_NAME], $types);
    }
}
