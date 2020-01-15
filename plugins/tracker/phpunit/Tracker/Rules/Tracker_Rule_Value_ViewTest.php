<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Rule;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_Rule_List;
use Tracker_Rule_List_View;

//phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_Rule_List_ViewTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testFetch()
    {
        $rule = Mockery::mock(Tracker_Rule_List::class);
        $rule->id                = 'id';
        $rule->tracker_id        = 'tracker_id';
        $rule->source_field      = 'source_field';
        $rule->target_field      = 'target_field';
        $rule->source_value      = 'source_value_1';
        $rule->target_value      = 'target_value_2';

        $view = new Tracker_Rule_List_View($rule);
        $this->assertEquals('#id@tracker_id source_field(source_value_1) => target_field(target_value_2)', $view->fetch());
    }
}
