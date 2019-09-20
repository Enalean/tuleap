<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2005. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2005
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

require_once __DIR__ . '/../../../src/www/include/pre.php';

use Tuleap\Statistics\Frequencies\GraphDataBuilder\SampleFactory;
use Tuleap\Statistics\Frequencies\GraphDataBuilder\SampleGraph;

// First, check plugin availability
$pluginManager = PluginManager::instance();
$p = $pluginManager->getPluginByName('statistics');
if (! $p || ! $pluginManager->isPluginAvailable($p)) {
    $GLOBALS['Response']->redirect('/');
}

// Grant access only to site admin
if (! UserManager::instance()->getCurrentUser()->isSuperUser()) {
    $GLOBALS['Response']->redirect('/');
}

$sampleFactory = new SampleFactory();

$request = HTTPRequest::instance();
$sampleFactory->setSample($request->get('data'));


//advanced search
if ($request->get('start') && $request->get('end') && $request->get('filter')) {
    //if user make a mistake in the advanced search
    if (strtotime($request->get('start')) >= strtotime($request->get('end')) || $request->get('start') == '' || $request->get('end') == '') {
        $sampleFactory->setSample('session');
        $statGraph   = $sampleFactory->getSimple(date('Y'), 0, 0);
        $sampleGraph = new SampleGraph(
            $statGraph->fetchData(),
            'session',
            'month',
            $statGraph->getTitlePeriod(),
            0,
            null,
            null,
            null,
            null
        );
    } else {
        $statGraph = $sampleFactory->getAdvanced(
            $request->get('start'),
            $request->get('end'),
            $request->get('filter')
        );

        switch ($request->get('filter')) {
            case 'month1':
                $sampleGraph = new SampleGraph(
                    $statGraph->fetchMonthData(),
                    $request->get('data'),
                    $statGraph->getFilter(),
                    $statGraph->getTitlePeriod(),
                    2,
                    null,
                    null,
                    $request->get('start'),
                    $request->get('end')
                );
                break;

            case 'day1':
                $sampleGraph = new SampleGraph(
                    $statGraph->fetchDayData(),
                    $request->get('data'),
                    $statGraph->getFilter(),
                    $statGraph->getTitlePeriod(),
                    3,
                    null,
                    null,
                    $request->get('start'),
                    $request->get('end')
                );
                break;

            default:
                $sampleGraph = new SampleGraph(
                    $statGraph->fetchData(),
                    $request->get('data'),
                    $statGraph->getFilter(),
                    $statGraph->getTitlePeriod(),
                    $request->get('advsrch'),
                    null,
                    null,
                    null,
                    null
                );
                break;
        }
    }
} else { //simple search
    $statGraph = $sampleFactory->getSimple(
        $request->get('year'),
        $request->get('month'),
        $request->get('day')
    );

    $sampleGraph = new SampleGraph(
        $statGraph->fetchData(),
        $request->get('data'),
        $statGraph->getFilter(),
        $statGraph->getTitlePeriod(),
        $request->get('advsrch'),
        $request->get('year'),
        $request->get('month'),
        null,
        null
    );
}

$sampleg = $sampleGraph->display();
