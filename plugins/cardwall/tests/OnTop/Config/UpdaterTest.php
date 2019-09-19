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
require_once dirname(__FILE__) .'/../../bootstrap.php';

class Cardwall_OnTop_Config_UpdaterTest extends TuleapTestCase
{

    public function itScheduleExecuteOnCommands()
    {
        $request  = mock('Codendi_Request');
        $c1       = mock('Cardwall_OnTop_Config_Command');
        $c2       = mock('Cardwall_OnTop_Config_Command');
        $updater  = new Cardwall_OnTop_Config_Updater();
        $updater->addCommand($c1);
        $updater->addCommand($c2);

        stub($c1)->execute($request)->once();
        stub($c2)->execute($request)->once();

        $updater->process($request);
    }
}
