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

use PHPUnit\Framework\TestCase;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\ProgramIncrementArtifactLinkType;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class scaled_agilePluginTest extends TestCase
{
    public function testProvidesArtLinkTypes(): void
    {
        $plugin  = new scaled_agilePlugin(1);
        $natures = [];
        $params  = ['natures' => &$natures];
        $plugin->getArtifactLinkNatures($params);

        self::assertEquals([new ProgramIncrementArtifactLinkType()], $natures);
    }

    public function testProvidesNaturePresenterWhenTheTypeIsExposedByThePlugin(): void
    {
        $plugin    = new scaled_agilePlugin(1);
        $presenter = null;
        $params    = ['shortname' => ProgramIncrementArtifactLinkType::ART_LINK_SHORT_NAME, 'presenter' => &$presenter];
        $plugin->getNaturePresenter($params);

        self::assertEquals(new ProgramIncrementArtifactLinkType(), $presenter);
    }

    public function testDoesNotProvideNaturePresenterWhenTheTypeIsNotExposedByThePlugin(): void
    {
        $plugin  = new scaled_agilePlugin(1);
        $presenter = null;
        $params  = ['shortname' => 'something', 'presenter' => &$presenter];
        $plugin->getNaturePresenter($params);

        self::assertNull($presenter);
    }

    public function testExposesSystemArtifactLinkType(): void
    {
        $plugin  = new scaled_agilePlugin(1);
        $natures = [];
        $params  = ['natures' => &$natures];
        $plugin->trackerAddSystemNatures($params);

        self::assertEquals([ProgramIncrementArtifactLinkType::ART_LINK_SHORT_NAME], $natures);
    }
}
