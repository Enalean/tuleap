<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

namespace Tuleap\Trafficlights;

use MVC2_PluginController;
use Codendi_Request;

abstract class TrafficlightsController extends MVC2_PluginController {

    const NAME = 'trafficlights';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Project
     */
    protected $project;

    public function __construct(Codendi_Request $request, Config $config) {
        parent::__construct(self::NAME, $request);

        $this->project = $request->getProject();
        $this->config  = $config;
    }

    public function getBreadcrumbs() {
        return new NoCrumb();
    }

    protected function getTemplatesDir() {
        return TRAFFICLIGHTS_BASE_DIR.'/templates';
    }
}
