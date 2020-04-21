<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Cardwall_OnTop_Config_Command_DeleteColumnsTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalResponseMock;
    use \Tuleap\GlobalLanguageMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tracker_id = 666;
        $tracker = \Mockery::spy(\Tracker::class)->shouldReceive('getId')->andReturns($this->tracker_id)->getMock();

        $this->field_dao = \Mockery::spy(\Cardwall_OnTop_ColumnMappingFieldDao::class);
        $this->value_dao = \Mockery::spy(\Cardwall_OnTop_ColumnMappingFieldValueDao::class);

        $this->dao     = \Mockery::spy(\Cardwall_OnTop_ColumnDao::class);
        $this->command = new Cardwall_OnTop_Config_Command_DeleteColumns($tracker, $this->dao, $this->field_dao, $this->value_dao);
    }

    public function testItDeletesOneColumn(): void
    {
        $request = new HTTPRequest();
        $request->set(
            'column',
            array(
                12 => array('label' => 'Todo'),
                14 => array('label' => '')
            )
        );
        $this->field_dao->shouldReceive('deleteCardwall')->never();
        $this->value_dao->shouldReceive('deleteForColumn')->with($this->tracker_id, 14)->once();
        $this->dao->shouldReceive('delete')->with($this->tracker_id, 14)->once();
        $this->command->execute($request);
    }

    public function testItDeletes2Columns(): void
    {
        $request = new HTTPRequest();
        $request->set(
            'column',
            array(
                12 => array('label' => 'Todo'),
                13 => array('label' => ''),
                14 => array('label' => '')
            )
        );
        $this->field_dao->shouldReceive('deleteCardwall')->never();
        $this->value_dao->shouldReceive('deleteForColumn')->with($this->tracker_id, 13)->once();
        $this->value_dao->shouldReceive('deleteForColumn')->with($this->tracker_id, 14)->once();
        $this->dao->shouldReceive('delete')->with($this->tracker_id, 13)->once();
        $this->dao->shouldReceive('delete')->with($this->tracker_id, 14)->once();
        $this->command->execute($request);
    }

    public function testItDeleteFieldMappingWhenRemoveTheLastColumn(): void
    {
        $request = new HTTPRequest();
        $request->set('column', array(14 => array('label' => '')));
        $this->field_dao->shouldReceive('deleteCardwall')->with($this->tracker_id)->once();
        $this->value_dao->shouldReceive('deleteForColumn')->with($this->tracker_id, 14)->once();
        $this->dao->shouldReceive('delete')->with($this->tracker_id, 14)->once();
        $this->command->execute($request);
    }

    public function testItDeletesAllColumns(): void
    {
        $request = new HTTPRequest();
        $request->set(
            'column',
            array(
                12 => array('label' => ''),
                13 => array('label' => ''),
            )
        );
        $this->field_dao->shouldReceive('deleteCardwall')->with($this->tracker_id)->once();
        $this->value_dao->shouldReceive('deleteForColumn')->with($this->tracker_id, 12)->once();
        $this->value_dao->shouldReceive('deleteForColumn')->with($this->tracker_id, 13)->once();
        $this->dao->shouldReceive('delete')->with($this->tracker_id, 12)->once();
        $this->dao->shouldReceive('delete')->with($this->tracker_id, 13)->once();
        $this->command->execute($request);
    }
}
