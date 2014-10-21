<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

abstract class Git_HTTP_Command {
    protected $env;

    public function __construct() {
        $this->env = array(
            'GIT_PROJECT_ROOT'    => Config::get('sys_data_dir')."/gitolite/repositories",
            'GIT_HTTP_EXPORT_ALL' => "1",
            'PATH_INFO'           => $_SERVER['PATH_INFO'],
            'QUERY_STRING'        => $_SERVER['QUERY_STRING'],
            'REQUEST_METHOD'      => $_SERVER['REQUEST_METHOD'],
        );

        $this->appendToEnv('CONTENT_TYPE');
        $this->appendToEnv('HTTP_ACCEPT_ENCODING');

    }

    abstract public function getCommand();

    public function getEnvironment() {
        return $this->env;
    }

    protected function appendToEnv($variable_name) {
        if (isset($_SERVER[$variable_name])) {
            $this->env[$variable_name] = $_SERVER[$variable_name];
        }
    }
}
