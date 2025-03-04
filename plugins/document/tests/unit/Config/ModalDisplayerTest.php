<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Document\Config;

use Tuleap\Docman\Tests\Stub\FilenamePatternRetrieverStub;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ModalDisplayerTest extends TestCase
{
    public function testItReturnsTrueWhenHistoryEnforcementSettingsAllowsChangelogModal(): void
    {
        $changelog_modal_displayer = new ModalDisplayer(
            FilenamePatternRetrieverStub::buildWithNoPattern(),
            new HistoryEnforcementSettings(true)
        );

        self::assertTrue($changelog_modal_displayer->isChangelogModalDisplayedAfterDragAndDrop(101));
    }

    public function testItReturnsTrueWhenAFilenamePatternExists(): void
    {
        $changelog_modal_displayer = new ModalDisplayer(
            FilenamePatternRetrieverStub::buildWithPattern('${TITLE}-Andale'),
            new HistoryEnforcementSettings(false)
        );

        self::assertTrue($changelog_modal_displayer->isChangelogModalDisplayedAfterDragAndDrop(101));
    }

    public function testItReturnsFalseWhenThereIsNoPatternOrTheSettingsDoesNotAllowChangelogModal(): void
    {
        $changelog_modal_displayer = new ModalDisplayer(
            FilenamePatternRetrieverStub::buildWithNoPattern(),
            new HistoryEnforcementSettings(false)
        );

        self::assertFalse($changelog_modal_displayer->isChangelogModalDisplayedAfterDragAndDrop(101));
    }
}
