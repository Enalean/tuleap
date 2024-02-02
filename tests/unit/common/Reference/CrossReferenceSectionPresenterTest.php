<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Reference;

use Tuleap\Test\Builders\CrossReferencePresenterBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class CrossReferenceSectionPresenterTest extends TestCase
{
    public function testWithAdditionalCrossReference(): void
    {
        $a_ref       = CrossReferencePresenterBuilder::get(1)->build();
        $another_ref = CrossReferencePresenterBuilder::get(2)->build();

        $section     = new CrossReferenceSectionPresenter("my section", [$a_ref]);
        $new_section = $section->withAdditionalCrossReference($another_ref);

        self::assertEquals("my section", $new_section->label);
        self::assertEquals(
            [$a_ref, $another_ref],
            $new_section->cross_references
        );
    }
}
