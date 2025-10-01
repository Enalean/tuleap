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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Reference;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class CrossReferenceByDirectionPresenterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testSortCrossReferenceNatureAlphabetically(): void
    {
        $a_targets = new CrossReferenceNaturePresenter('A Targets', '', []);
        $b_targets = new CrossReferenceNaturePresenter('b Targets', '', []);

        $d_sources = new CrossReferenceNaturePresenter('d Sources', '', []);
        $e_sources = new CrossReferenceNaturePresenter('E Sources', '', []);
        $f_sources = new CrossReferenceNaturePresenter('f Sources', '', []);

        $nature = new CrossReferenceByDirectionPresenter([$e_sources, $d_sources, $f_sources], [$b_targets, $a_targets]);

        self::assertCount(2, $nature->targets_by_nature);
        self::assertCount(3, $nature->sources_by_nature);
        self::assertEquals('A Targets', $nature->targets_by_nature[0]->label);
        self::assertEquals('b Targets', $nature->targets_by_nature[1]->label);
        self::assertEquals('d Sources', $nature->sources_by_nature[0]->label);
        self::assertEquals('E Sources', $nature->sources_by_nature[1]->label);
        self::assertEquals('f Sources', $nature->sources_by_nature[2]->label);
    }
}
