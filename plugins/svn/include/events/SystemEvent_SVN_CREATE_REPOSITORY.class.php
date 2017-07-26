<?php
/**
 * Copyright (c) Enalean, 2016-2017. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Svn\EventRepository;

use SystemEvent;
use Backend;
use ForgeConfig;

class SystemEvent_SVN_CREATE_REPOSITORY extends SystemEvent {
    const NAME = 'SystemEvent_SVN_CREATE_REPOSITORY';

    public function verbalizeParameters($with_link) {
        $path            = $this->getRequiredParameter(0);
        $project_id      = $this->getRequiredParameter(1);
        $repository_name = $this->getRequiredParameter(2);

        $txt = 'project: '. $this->verbalizeProjectId($project_id, $with_link) .', path: '.$path.', name: '.$repository_name;
        return $txt;
    }

    public function process() {
        $system_path     = $this->getRequiredParameter(0);
        $project_id      = (int)$this->getRequiredParameter(1);
        $repository_name = $this->getRequiredParameter(2);

        $backendSystem = Backend::instance('System');

        // Force NSCD flush (otherwise uid & gid will not exist)
        $backendSystem->flushNscdAndFsCache();

        $backendSvn = Backend::instance('SVN');
        if (! $backendSvn->createRepositorySVN($project_id, $system_path, ForgeConfig::get('tuleap_dir').'/plugins/svn/bin/')) {
            $this->error("Could not create/initialize project SVN repository");
            return false;
        }

        $this->done();

        return true;
    }

    /**
     * @return string
     */
    public static function serializeParameters(array $parameters)
    {
        return json_encode($parameters);
    }

    public function getParametersAsArray()
    {
        $unserialized_parameters = json_decode($this->getParameters(), true);
        if ($unserialized_parameters === null) {
            return parent::getParametersAsArray();
        }
        return array_values($unserialized_parameters);
    }
}