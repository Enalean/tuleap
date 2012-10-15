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

require_once 'RemoteSSHConfig.class.php';

class Git_Driver_Gerrit_RemoteSSHCommand {

    const SUCCESS = 0;
    
    public function execute(Git_Driver_Gerrit_RemoteSSHConfig $config, $cmd) {
        $port          = $config->getPort();
        $host          = $config->getHost();
        $login         = $config->getLogin();
        $identity_file = $config->getIdentityFile();
        $result = $this->sshExec("-p $port -i $identity_file $login@$host $cmd");
        $exit_code = $result['exit_code'];
        $std_err = $result['std_err'];
        $std_out = $result['std_out'];
        if ($exit_code != self::SUCCESS) {
            throw new RemoteSSHCommandFailure($exit_code, $std_out, $std_err);
        }
        
    }
    
    protected function sshExec($cmd) {
        $output;
        $exit_code;
        $filename = '/tmp/stderr_'.  uniqid();
        exec("ssh $cmd 2>$filename", $output, $exit_code);
        $stderr = file_get_contents($filename);
        unlink($filename);
        return array('exit_code' => $exit_code,
                     'std_out'   => implode(PHP_EOL, $output), 
                     'std_err'   => $stderr);
    }
}

class RemoteSSHCommandFailure extends Exception {

    private $exit_code;
    private $std_out;
    private $std_err;
    
    function __construct($exit_code, $std_out, $std_err) {
        parent::__construct("");
        $this->exit_code = $exit_code;
        $this->std_out = $std_out;
        $this->std_err = $std_err;
    }

    
    /**
     * @return int
     */
    public function getExitCode() {
        return $this->exit_code;
    }

    /**
     * @return string with PHP_EOL as line terminators
     */
    public function getStdErr() {
        return $this->std_err;
        
    }

    /**
     * @return string with PHP_EOL as line terminators
     */
    public function getStdOut() {
        return $this->std_out;
    }
}

?>
