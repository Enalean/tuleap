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

namespace Tuleap\SystemEvent;

use System_Command_CommandException;
use SystemEvent;
use Tuleap\Svn\ApacheConfGenerator;

class SystemEvent_PROJECT_ACTIVE extends SystemEvent // phpcs:ignore
{
    /**
     * @var ApacheConfGenerator
     */
    private $apache_conf_generator;

    public function injectDependencies(
        ApacheConfGenerator $apache_conf_generator
    ) {
        $this->apache_conf_generator  = $apache_conf_generator;
    }

    /**
     * Verbalize the parameters so they are readable and much user friendly in
     * notifications
     *
     * @param bool $with_link true if you want links to entities. The returned
     *                        string will be html instead of plain/text
     *
     * @return string
     */
    public function verbalizeParameters($with_link)
    {
        return 'project: ' . $this->verbalizeProjectId($this->getIdFromParam($this->parameters), $with_link);
    }

    public function process()
    {
        try {
            $this->apache_conf_generator->generate();
            $this->done();
        } catch (System_Command_CommandException $e) {
            $this->error($e->getMessage());
        }
    }
}
