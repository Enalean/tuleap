<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Git;

use Git_RemoteServer_GerritServer;
use User_SSHKeyValidator;

class AdminGerritBuilder
{
    /**
     * @var User_SSHKeyValidator
     */
    private $ssh_key_validator;

    public function __construct(User_SSHKeyValidator $ssh_key_validator)
    {
        $this->ssh_key_validator = $ssh_key_validator;
    }

    public function buildFromRequest(array $request)
    {
        $gerrit_server                         = array();
        $gerrit_server['host']                 = $request['host'];
        $gerrit_server['ssh_port']             = $request['ssh_port'];
        $gerrit_server['http_port']            = $request['http_port'];
        $gerrit_server['login']                = $request['login'];
        $gerrit_server['identity_file']        = $request['identity_file'];
        $gerrit_server['replication_ssh_key']  = $this->getValidatedSSHKey($request['replication_key']);
        $gerrit_server['use_ssl']              = isset($request['use_ssl']) ? $request['use_ssl'] : false;
        $gerrit_server['http_password']        = $request['http_password'];
        $gerrit_server['replication_password'] = $request['replication_password'];
        $gerrit_server['auth_type']            = $request['auth_type'];
        $gerrit_server['gerrit_version']       = Git_RemoteServer_GerritServer::GERRIT_VERSION_2_8_PLUS;

        return $gerrit_server;
    }

    /**
     * @return string
     */
    private function getValidatedSSHKey($ssh_key)
    {
        $ssh_key = trim($ssh_key);
        if ($ssh_key === '') {
            return '';
        }
        if (strpos($ssh_key, "\n") !== false) {
            $GLOBALS['Response']->addFeedback(
                \Feedback::WARN,
                dgettext('tuleap-git', 'Only one replication SSH key is allowed by server')
            );
            return '';
        }
        $validated_key = $this->ssh_key_validator->validateAllKeys(array($ssh_key));

        if (empty($validated_key)) {
            return '';
        }

        return $ssh_key;
    }
}
