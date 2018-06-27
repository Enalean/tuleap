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

namespace Tuleap\CLI;

use Tuleap\Event\Dispatchable;
use Symfony\Component\Console\Command\Command;

class CLICommandsCollector implements Dispatchable
{
    const NAME = 'collectCLICommands';
    /**
     * @var Application
     */
    private $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function addCommand(Command $command)
    {
        return $this->application->add($command);
    }
}
