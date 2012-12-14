<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and_or modify
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
 * along with Tuleap. If not, see <http:__www.gnu.org_licenses_>.
 */

require_once 'common/include/Config.class.php';
require_once 'RemoteSSHConfig.class.php';
require_once 'RemoteSSHCommandFailure.class.php';

class Git_Driver_Gerrit_RemoteSSHCommand {

    const SUCCESS = 0;
    
    /** Logger */
    private $logger;

    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }
    
    public function execute(Git_Driver_Gerrit_RemoteSSHConfig $config, $cmd) {
        $port          = $config->getSSHPort();
        $host          = $config->getHost();
        $login         = $config->getLogin();
        $identity_file = $config->getIdentityFile();
        $full_cmd      = "-p $port -i $identity_file $login@$host $cmd";
        $this->logger->info("executing $full_cmd");
        $result = $this->sshExec($full_cmd);
        $this->logger->debug('Result: '. var_export($result, 1));
        $exit_code = $result['exit_code'];
        $std_out = $result['std_out'];
        if ($exit_code != self::SUCCESS) {
            $std_err = $result['std_err'];
            throw new Git_Driver_Gerrit_RemoteSSHCommandFailure($exit_code, $std_out, $std_err);
        } else {
            return $std_out;
        }
    }

    protected function sshExec($cmd) {
        $filename = tempnam(Config::get('tmp_dir'), 'stderr_');
        exec("ssh $cmd 2>$filename", $output, $exit_code);
        $stderr = file_get_contents($filename);
        unlink($filename);
        return array(
            'exit_code' => $exit_code,
            'std_out'   => implode(PHP_EOL, $output),
            'std_err'   => $stderr
        );
    }
}
?>
