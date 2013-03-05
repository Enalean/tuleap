<?php

/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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


require_once dirname(__FILE__).'/../../../../include/constants.php';
require_once GIT_BASE_DIR . '/Git/Driver/Gerrit/RemoteSSHCommand.class.php';
require_once GIT_BASE_DIR . '/Git/Driver/Gerrit.class.php';

class Git_Driver_Gerrit_RemoteSSHCommand_Test extends TuleapTestCase {

    protected $host = 'gerrit.example.com';

    protected $ssh_port = '29418';

    protected $http_port = '80';

    protected $login = 'gerrit';

    protected $identity_file = '/path/to/codendiadm/.ssh/id_rsa';

    protected $replication_key = '458rfregfrehjf ghvghh@dfrdgf';

    /** @var Git_Driver_Gerrit_RemoteSSHConfig */
    private $config;

    /**
     * @var RemoteSshCommand
     */
    protected $ssh;

    public function setUp() {
        parent::setUp();
        $this->ssh = partial_mock(
            'Git_Driver_Gerrit_RemoteSSHCommand',
            array('sshExec')
        );

        $this->logger = mock('Logger');

        $this->ssh->__construct($this->logger);
        $this->config = new Git_RemoteServer_GerritServer(1, $this->host, $this->ssh_port, $this->http_port, $this->login, $this->identity_file, $this->replication_key);
    }

    public function itExecutesTheCreateCommandOnTheRemoteServer() {
        $cmd = '-p 29418 -i /path/to/codendiadm/.ssh/id_rsa gerrit@gerrit.example.com a_remote_command';
        expect($this->ssh)->sshExec($cmd)->once();
        $this->ssh->execute($this->config, 'a_remote_command');
    }

    public function itThrowsAnExceptionWithTheErrorCode() {
        stub($this->ssh)->sshExec()->returns(array('exit_code' => 125, 'std_err' => '', 'std_out' =>''));
        try {
            $this->ssh->execute($this->config, 'someFailingCommand');
            $this->fail('expected exception');
        } catch (Git_Driver_Gerrit_RemoteSSHCommandFailure $e) {
            $this->assertEqual($e->getExitCode(), 125);
        }
    }

    public function itThrowsEnExceptionWithErrorCode_withoutStubbingCallToSystem() {
        $ssh_command = new Git_Driver_Gerrit_RemoteSSHCommand(mock('Logger'));
        try {
            $ssh_command->execute($this->config, 'someFailingCommand');
            $this->fail('expected exception');
        } catch (Git_Driver_Gerrit_RemoteSSHCommandFailure $e) {
            $this->assertTrue($e->getExitCode() > 0, "As the command didn't succeed we expect an error code > 0");
        }
    }

    public function itRaisesAnErrorThatContainsTheStdErr_withoutStubbingCallToSystem() {
        $ssh_command = new Git_Driver_Gerrit_RemoteSSHCommand(mock('Logger'));
        try {
            $ssh_command->execute($this->config, 'someFailingCommand');
            $this->fail('expected exception');
        } catch (Git_Driver_Gerrit_RemoteSSHCommandFailure $e) {
            $this->assertNotEmpty($e->getStdErr(), "As the command didn't succeed we something on standard error");
        }
    }
    
    public function itRaisesAnErrorThatContainsTheStdOut() {
        stub($this->ssh)->sshExec()->returns(array('exit_code' => 125, 
            'std_err' => 'cant access subdirectory toto', 
            'std_out' => 'somefile someotherfile
                          yetanotherfile'));
        try {
            $this->ssh->execute($this->config, 'ls -aR *');
            $this->fail('expected exception');
        } catch (Git_Driver_Gerrit_RemoteSSHCommandFailure $e) {
            $this->assertNotEmpty($e->getStdOut(), "ls always produces some output on standard out");
        }
    }
    
    public function itRaisesAnErrorThatContainsTheStdErr() {
        stub($this->ssh)->sshExec()->returns(
                  array('exit_code' => 1,
                        'std_out'   => '',
                        'std_err'   => 'command someFailingCommand not found\nOn host '.$this->host));
        try {
            $this->ssh->execute($this->config, 'someFailingCommand');
            $this->fail('expected exception');
        } catch (Git_Driver_Gerrit_RemoteSSHCommandFailure $e) {
            $this->assertEqual($e->getStdErr(), 'command someFailingCommand not found\nOn host '.$this->host);
        }
    }
    
    public function itRemovesTemporaryFiles() {
        $nb_files_b4 = count(scandir('/tmp'));
        $ssh_command = new Git_Driver_Gerrit_RemoteSSHCommand(mock('Logger'));
        try {
            $ssh_command->execute($this->config, 'someFailingCommand');
        } catch (Git_Driver_Gerrit_RemoteSSHCommandFailure $e) {
            
        }

        $nb_files_after = count(scandir('/tmp'));
        $this->assertEqual($nb_files_b4, $nb_files_after, 
                "expected number of files in /tmp to be the same before ($nb_files_b4) and after ($nb_files_after) the test");
    }

    public function itReturnsStdout(){
        stub($this->ssh)->sshExec()->returns(array('exit_code' => 0, 'std_out' => 'Some successful output'));
        try {
            $result = $this->ssh->execute($this->config, 'Some successful command');
        } catch (Git_Driver_Gerrit_RemoteSSHCommandFailure $e) {
            $this->fail('unexpected exception');
        }
        $this->assertEqual($result, 'Some successful output');
    }
    
    public function itLogsEveryCommand() {
        $expected_result = array('exit_code' => 0, 'std_err' => '', 'std_out' => 'Some successful output');
        stub($this->ssh)->sshExec()->returns($expected_result);
        $cmd = '-p 29418 -i /path/to/codendiadm/.ssh/id_rsa gerrit@gerrit.example.com create-group toto --username johan';
        expect($this->logger)->info("executing $cmd")->once();
        expect($this->logger)->debug("Result: ". var_export($expected_result, 1))->once();
        $this->ssh->execute($this->config, 'create-group toto --username johan');
    }
}

class RemoteSSHCommandFailureTest extends TuleapTestCase {
    
    public function itConcatenatesEverythingInGetMessage() {
        $exit_code = 133;
        $std_err   = 'some error';
        $std_out   = 'output for the people';
        $e         = new Git_Driver_Gerrit_RemoteSSHCommandFailure($exit_code, $std_out, $std_err);
        $this->assertStringContains($e->getMessage(), "exit_code: $exit_code");
        $this->assertStringContains($e->getMessage(), "std_out: $std_out");
        $this->assertStringContains($e->getMessage(), "std_err: $std_err");
    }
}
?>
