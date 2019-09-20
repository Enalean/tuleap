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

/**
 * I am a command in a chain of responsability
 *
 * @see http://en.wikipedia.org/wiki/Chain-of-responsibility_pattern
 */
abstract class Git_GitoliteHousekeeping_ChainOfResponsibility_Command
{

    /** @var Git_GitoliteHousekeeping_ChainOfResponsibility_Command */
    private $next_command;

    public function __construct()
    {
        $this->setNextCommand(new Git_GitoliteHousekeeping_ChainOfResponsibility_DoNothing());
    }

    public function setNextCommand(Git_GitoliteHousekeeping_ChainOfResponsibility_Command $next_command)
    {
        $this->next_command = $next_command;
    }

    public function getNextCommand()
    {
        return $this->next_command;
    }

    public function executeNextCommand()
    {
        $this->next_command->execute();
    }

    /** @return void */
    abstract public function execute();
}
