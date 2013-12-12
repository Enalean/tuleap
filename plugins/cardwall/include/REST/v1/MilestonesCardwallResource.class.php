<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
namespace Tuleap\Cardwall\REST\v1;

use \Luracast\Restler\RestException;
use \Tuleap\REST\Header;
use \Planning_Milestone;
use \Cardwall_OnTop_ConfigFactory;

class MilestonesCardwallResource {
    /** @var Cardwall_OnTop_ConfigFactory */
    private $config_factory;

    public function __construct(Cardwall_OnTop_ConfigFactory $config_factory) {
        $this->config_factory = $config_factory;
    }

    public function options(Planning_Milestone $milestone) {
        $config = $this->config_factory->getOnTopConfig($milestone->getArtifact()->getTracker());

        if ($config->isEnabled()) {
            $this->sendAllowHeaderForCardwall();
        } else {
            throw new RestException(404);
        }
    }

    private function sendAllowHeaderForCardwall() {
        Header::allowOptionsGet();
    }
}
