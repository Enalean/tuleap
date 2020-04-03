<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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

namespace Tuleap\Cardwall\OnTop\Config;

require_once __DIR__ . '/../../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Field_List;

class ColumnFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Mockery\MockInterface */
    private $field;

    /** @var Mockery\MockInterface */
    private $dao;

    /** @var \Cardwall_OnTop_Config_ColumnFactory */
    private $column_factory;

    public function setUp(): void
    {
        parent::setUp();

        $this->dao        = Mockery::mock(\Cardwall_OnTop_ColumnDao::class);
        $this->field      = Mockery::mock(Tracker_FormElement_Field_List::class)->makePartial();

        $this->column_factory = new \Cardwall_OnTop_Config_ColumnFactory($this->dao);
    }

    public function testItShouldNotFatalErrorOnInvalidBindValue()
    {
        $filter = [123, 234];
        $bind   = Mockery::mock(\Tracker_FormElement_Field_List_Bind::class)->makePartial();

        $this->field->shouldReceive("getBind")->andReturn($bind);
        $this->field->shouldReceive("isNone")->andReturnFalse();
        $bind->shouldReceive("getValue")->andThrow(new \Tracker_FormElement_InvalidFieldValueException());

        $this->column_factory->getFilteredRendererColumns($this->field, $filter);
    }
}
