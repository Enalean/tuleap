<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\BrowserDetection;

class BrowserUsageSessionsStatisticsRetriever
{
    private \SessionDao $session_dao;

    public function __construct(\SessionDao $session_dao)
    {
        $this->session_dao = $session_dao;
    }

    public function getStatistics(\DateTimeImmutable $current_time): BrowserUsageSessions
    {
        $nb_really_outdated     = 0;
        $nb_supported           = 0;
        $session_nb_user_agents = $this->session_dao->countUserAgentsOfActiveSessions($current_time->getTimestamp(), \ForgeConfig::getInt('sys_session_lifetime'));

        foreach ($session_nb_user_agents as $session_nb_user_agent) {
            $detected_browser = DetectedBrowser::detectFromUserAgentString($session_nb_user_agent['user_agent']);
            if ($detected_browser->isAnOutdatedBrowser()) {
                $nb_really_outdated += $session_nb_user_agent['nb'];
            } else {
                $nb_supported += $session_nb_user_agent['nb'];
            }
        }

        return new BrowserUsageSessions($nb_really_outdated, $nb_supported);
    }
}
