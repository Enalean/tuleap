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

use PHPUnit\Framework\TestCase;

class CrossReferenceNaturePresenterTest extends TestCase
{
    public function testWithAdditionalCrossReference(): void
    {
        $a_ref       = $this->getCrossReferencePresenter(1);
        $another_ref = $this->getCrossReferencePresenter(2);

        $section = new CrossReferenceSectionPresenter("my section", [$a_ref]);
        $nature  = new CrossReferenceNaturePresenter("My Nature", "fas fa-candy-cane", [$section]);

        $new_nature = $nature->withAdditionalCrossReferencePresenter("my section", $another_ref);

        self::assertEquals("My Nature", $new_nature->label);
        self::assertCount(1, $new_nature->sections);
        self::assertEquals("my section", $new_nature->sections[0]->label);
        self::assertEquals([$a_ref, $another_ref], $new_nature->sections[0]->cross_references);
    }

    public function testSortSectionAlphabetically(): void
    {
        $a_ref = $this->getCrossReferencePresenter(1);
        $b_ref = $this->getCrossReferencePresenter(2);
        $c_ref = $this->getCrossReferencePresenter(3);

        $c_section = new CrossReferenceSectionPresenter("C Section", [$c_ref]);
        $a_section = new CrossReferenceSectionPresenter("A Section", [$a_ref]);
        $nature  = new CrossReferenceNaturePresenter("My Nature", "fas fa-candy-cane", [$c_section, $a_section]);
        $nature = $nature->withAdditionalCrossReferencePresenter("b Section", $b_ref);

        self::assertEquals("My Nature", $nature->label);
        self::assertCount(3, $nature->sections);
        self::assertEquals("A Section", $nature->sections[0]->label);
        self::assertEquals("b Section", $nature->sections[1]->label);
        self::assertEquals("C Section", $nature->sections[2]->label);
        self::assertEquals([$a_ref], $nature->sections[0]->cross_references);
        self::assertEquals([$b_ref], $nature->sections[1]->cross_references);
        self::assertEquals([$c_ref], $nature->sections[2]->cross_references);
    }

    private function getCrossReferencePresenter(int $id): CrossReferencePresenter
    {
        return new CrossReferencePresenter(
            $id,
            "type",
            "reference",
            "url",
            "delete_url",
            1,
            "whatever",
            null,
            [],
            null
        );
    }
}
