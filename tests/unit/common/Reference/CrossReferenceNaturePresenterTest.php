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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CrossReferenceNaturePresenterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testWithAdditionalCrossReference(): void
    {
        $a_ref       = new CrossReferencePresenter(1, "type", "title", "url", "delete_url", 1, "whatever", null);
        $another_ref = new CrossReferencePresenter(2, "type", "reference", "url", "delete_url", 1, "whatever", null);

        $section = new CrossReferenceSectionPresenter("my section", [$a_ref]);
        $nature  = new CrossReferenceNaturePresenter("My Nature", "fas fa-candy-cane", [$section]);

        $new_nature = $nature->withAdditionalCrossReference("my section", $another_ref);

        self::assertEquals("My Nature", $new_nature->label);
        self::assertCount(1, $new_nature->sections);
        self::assertEquals("my section", $new_nature->sections[0]->label);
        self::assertEquals([$a_ref, $another_ref], $new_nature->sections[0]->cross_references);
    }
}
