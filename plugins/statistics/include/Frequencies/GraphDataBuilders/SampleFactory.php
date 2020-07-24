<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Statistics\Frequencies\GraphDataBuilder;

use EventManager;
use Statistics_Event;

/**
 * Design pattern factory
 *
 */
class SampleFactory
{
    /**
     * $sample a Sample object
     *
     * @type Sample object
     */
    private $sample;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->sample = new SessionSample();
    }

    /**
     * setSample()
     *
     * @param string $character session by default
     */
    public function setSample($character = "session")
    {
        switch ($character) {
            case 'session':
                $this->sample = new SessionSample();
                break;

            case 'user':
                $this->sample = new UserSample();
                break;

            case 'forum':
                $this->sample = new ForumSample();
                break;

            case 'filedl':
                $this->sample = new FiledSample();
                break;

            case 'file':
                $this->sample = new FilerSample();
                break;

            case 'groups':
                $this->sample = new ProjectSample();
                break;

            case 'wikidl':
                $this->sample =  new WikiSample();
                break;

            case 'oartifact':
                $this->sample =  new OartifactSample();
                break;

            case 'cartifact':
                $this->sample =  new CartifactSample();
                break;

            default:
                $sample = new SessionSample();
                EventManager::instance()->processEvent(
                    Statistics_Event::FREQUENCE_STAT_SAMPLE,
                    [
                        'character' => $character,
                        'sample'    => &$sample
                    ]
                );
                $this->sample = $sample;
                break;
        }
    }

    /**
     * getSimple()
     *
     * @param int $year  0 by default
     * @param int $month 0 by default
     * @param int $day   0 by default
     *
     * @return Sample Object.
     */
    public function getSimple($year = 0, $month = 0, $day = 0)
    {
        $this->sample->initDateSimple($year, $month, $day);
        return $this->sample;
    }

    /**
     * getAdvanced()
     *
     * @param string $startdate the date the graph start
     * @param string $enddate   the date the graph end
     * @param string $filter    the filter of display
     * (group by month, group by day,group by hour, month, day)
     *
     * @return Sample Object.
     */
    public function getAdvanced($startdate, $enddate, $filter)
    {
        $this->sample->initDateAdvanced($startdate, $enddate, $filter);
        return $this->sample;
    }
}
