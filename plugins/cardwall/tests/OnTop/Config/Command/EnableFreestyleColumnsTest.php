<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once dirname(__FILE__) .'/../../../bootstrap.php';
require_once dirname(__FILE__) .'/../../../../../../tests/simpletest/common/include/builders/aRequest.php';

class Cardwall_OnTop_Config_Command_EnableFreestyleColumnsTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->tracker_id = 666;
        $tracker = mock('Tracker');
        stub($tracker)->getId()->returns($this->tracker_id);

        $this->dao     = mock('Cardwall_OnTop_Dao');
        $this->command = new Cardwall_OnTop_Config_Command_EnableFreestyleColumns($tracker, $this->dao);
    }

    public function itEnablesIfItIsNotAlreadyTheCase()
    {
        $request = aRequest()->with('use_freestyle_columns', '1')->build();
        stub($this->dao)->isFreestyleEnabled($this->tracker_id)->returns(false);
        stub($this->dao)->enableFreestyleColumns()->once($this->tracker_id);

        $this->command->execute($request);
    }

    public function itDoesNotEnableIfItIsNotAlreadyTheCase()
    {
        $request = aRequest()->with('use_freestyle_columns', '1')->build();
        stub($this->dao)->isFreestyleEnabled($this->tracker_id)->returns(true);
        stub($this->dao)->enableFreestyleColumns()->never();

        $this->command->execute($request);
    }

    public function itDisablesIfItIsNotAlreadyTheCase()
    {
        $request = aRequest()->with('use_freestyle_columns', '0')->build();
        stub($this->dao)->isFreestyleEnabled($this->tracker_id)->returns(true);
        stub($this->dao)->disableFreestyleColumns()->once($this->tracker_id);

        $this->command->execute($request);
    }

    public function itDoesNotDisableIfItIsNotAlreadyTheCase()
    {
        $request = aRequest()->with('use_freestyle_columns', '0')->build();
        stub($this->dao)->isFreestyleEnabled($this->tracker_id)->returns(false);
        stub($this->dao)->disableFreestyleColumns()->never();

        $this->command->execute($request);
    }
}
