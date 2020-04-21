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
final class Cardwall_OnTop_Config_Command_EnableFreestyleColumnsTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tracker_id = 666;
        $tracker = \Mockery::spy(\Tracker::class);
        $tracker->shouldReceive('getId')->andReturns($this->tracker_id);

        $this->dao     = \Mockery::spy(\Cardwall_OnTop_Dao::class);
        $this->command = new Cardwall_OnTop_Config_Command_EnableFreestyleColumns($tracker, $this->dao);
    }

    public function testItEnablesIfItIsNotAlreadyTheCase(): void
    {
        $request = new HTTPRequest();
        $request->set('use_freestyle_columns', '1');
        $this->dao->shouldReceive('isFreestyleEnabled')->with($this->tracker_id)->andReturns(false);
        $this->dao->shouldReceive('enableFreestyleColumns')->once($this->tracker_id);

        $this->command->execute($request);
    }

    public function testItDoesNotEnableIfItIsNotAlreadyTheCase(): void
    {
        $request = new HTTPRequest();
        $request->set('use_freestyle_columns', '1');
        $this->dao->shouldReceive('isFreestyleEnabled')->with($this->tracker_id)->andReturns(true);
        $this->dao->shouldReceive('enableFreestyleColumns')->never();

        $this->command->execute($request);
    }

    public function testItDisablesIfItIsNotAlreadyTheCase(): void
    {
        $request = new HTTPRequest();
        $request->set('use_freestyle_columns', '0');
        $this->dao->shouldReceive('isFreestyleEnabled')->with($this->tracker_id)->andReturns(true);
        $this->dao->shouldReceive('disableFreestyleColumns')->once($this->tracker_id);

        $this->command->execute($request);
    }

    public function testItDoesNotDisableIfItIsNotAlreadyTheCase(): void
    {
        $request = new HTTPRequest();
        $request->set('use_freestyle_columns', '0');
        $this->dao->shouldReceive('isFreestyleEnabled')->with($this->tracker_id)->andReturns(false);
        $this->dao->shouldReceive('disableFreestyleColumns')->never();

        $this->command->execute($request);
    }
}
