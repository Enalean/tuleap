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
final class Cardwall_OnTop_Config_Command_UpdateColumnsTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tracker_id = 666;
        $tracker = \Mockery::spy(\Tracker::class);
        $tracker->shouldReceive('getId')->andReturns($this->tracker_id);

        $this->dao     = \Mockery::spy(\Cardwall_OnTop_ColumnDao::class);
        $this->command = new Cardwall_OnTop_Config_Command_UpdateColumns($tracker, $this->dao);
    }

    public function testItUpdatesAllColumns(): void
    {
        $request = new HTTPRequest();
        $request->set(
            'column',
            array(
                12 => array('label' => 'Todo', 'bgcolor' => '#000000'),
                13 => array('label' => ''),
                14 => array('label' => 'Done', 'bgcolor' => '#16ed9d')
            )
        );
        $this->dao->shouldReceive('save')->with($this->tracker_id, 12, 'Todo', 0, 0, 0)->once();
        $this->dao->shouldReceive('save')->with($this->tracker_id, 14, 'Done', 22, 237, 157)->once();
        $this->command->execute($request);
    }
}
