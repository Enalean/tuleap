<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

class Git_HTTP_CommandGitolite3 extends Git_HTTP_Command
{
    public function __construct(PFO_User $user, Git_HTTP_Command $command)
    {
        parent::__construct();

        $gitolite_user_info = posix_getpwnam('gitolite');
        $gitolite_home      = $gitolite_user_info['dir'];

        $this->env['SHELL']            = '/bin/sh';
        $this->env['REMOTE_USER']      = $user->getUserName();
        $this->env['GIT_HTTP_BACKEND'] = $command->getCommand();
        $this->env['HOME']             = $gitolite_home;
        $this->env['REMOTE_ADDR']      = HTTPRequest::instance()->getIPAddress();
        $this->env['TERM']             = 'linux';
        $this->appendToEnv('REQUEST_URI');
        $this->env['REMOTE_PORT'] = empty($_SERVER['REMOTE_PORT']) ? 'UNKNOWN' : $_SERVER['REMOTE_PORT'];
        $this->appendToEnv('SERVER_ADDR');
        $this->appendToEnv('SERVER_PORT');

        $this->env['GITOLITE_HTTP_HOME'] = $gitolite_home;
    }

    #[\Override]
    public function getCommand(): string
    {
        return 'sudo -E -u gitolite /usr/share/gitolite3/gitolite-shell';
    }
}
