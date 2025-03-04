<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Document\Tree;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TypeOptionsCollectionTest extends TestCase
{
    public function testAddPaneAfterEmptyArray(): void
    {
        $collection = new TypeOptionsCollection(ProjectTestBuilder::aProject()->build());

        $option = new SearchCriterionListOptionPresenter('folder', 'Folder');

        $collection->addOptionAfter('stuff', $option);

        self::assertSame(
            [
                $option,
            ],
            $collection->getOptions()
        );
    }

    public function testAddPaneAfterOneElement(): void
    {
        $collection = new TypeOptionsCollection(ProjectTestBuilder::aProject()->build());

        $folder = new SearchCriterionListOptionPresenter('folder', 'Folder');
        $file   = new SearchCriterionListOptionPresenter('file', 'File');

        $collection->addOption($folder);

        $collection->addOptionAfter('folder', $file);

        self::assertSame(
            [
                $folder,
                $file,
            ],
            $collection->getOptions()
        );
    }

    public function testAddPaneAfterTwoElements(): void
    {
        $collection = new TypeOptionsCollection(ProjectTestBuilder::aProject()->build());

        $folder = new SearchCriterionListOptionPresenter('folder', 'Folder');
        $file   = new SearchCriterionListOptionPresenter('file', 'File');
        $wiki   = new SearchCriterionListOptionPresenter('wiki', 'Wiki');

        $collection->addOption($folder);
        $collection->addOption($file);

        $collection->addOptionAfter('file', $wiki);

        self::assertSame(
            [
                $folder,
                $file,
                $wiki,
            ],
            $collection->getOptions()
        );
    }

    public function testAddPaneAfterInTheMiddle(): void
    {
        $collection = new TypeOptionsCollection(ProjectTestBuilder::aProject()->build());

        $folder = new SearchCriterionListOptionPresenter('folder', 'Folder');
        $file   = new SearchCriterionListOptionPresenter('file', 'File');
        $wiki   = new SearchCriterionListOptionPresenter('wiki', 'Wiki');

        $collection->addOption($folder);
        $collection->addOption($file);

        $collection->addOptionAfter('folder', $wiki);

        self::assertSame(
            [
                $folder,
                $wiki,
                $file,
            ],
            $collection->getOptions()
        );
    }
}
