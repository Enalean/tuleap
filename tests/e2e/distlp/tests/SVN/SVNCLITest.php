<?php
/**
 * Copyright (c) Enalean, 2017 - 2019. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tests\Selenium\SVN;

use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

class SVNCLITest extends TestCase
{
    private $init_pwd;

    protected function setUp(): void
    {
        parent::setUp();
        $this->init_pwd = getcwd();
        system('/bin/rm -rf /tmp/sample');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        chdir($this->init_pwd);
        system('/bin/rm -rf /tmp/sample');
    }

    public function testSVNLs()
    {
        $output = $this->getXML($this->getSvnCommand('alice', 'ls --xml https://reverse-proxy/svnplugin/svn-project-01/sample'));
        $content = [];
        foreach ($output->list->entry as $entry) {
            $content[] = (string) $entry->name;
        }
        $this->assertEqualsCanonicalizing(['tags', 'trunk', 'branches'], $content);
    }

    private function getSvnCommand(string $username, string $command): string
    {
        return 'svn --username ' . $username . ' --password "Correct Horse Battery Staple" --non-interactive --trust-server-cert ' . $command;
    }

    public function testWriteAccessByAlice()
    {
        chdir('/tmp');
        $this->command($this->getSvnCommand('alice', 'co https://reverse-proxy/svnplugin/svn-project-01/sample'));
        chdir('/tmp/sample');
        $checkedout_revision = $this->getWCRevision();
        $need_to_add = true;
        if (file_exists('trunk/README')) {
            $need_to_add = false;
        }
        file_put_contents('trunk/README', "foo\n", FILE_APPEND);
        if ($need_to_add) {
            $this->command('svn add trunk/README');
        }
        $this->command($this->getSvnCommand('alice', 'ci -m "this is a test"'));
        $this->command($this->getSvnCommand('alice', 'update'));
        $this->assertEquals($checkedout_revision + 1, $this->getWCRevision());
    }

    public function testWriteAccessDeniedToBob()
    {
        $got_exception = false;
        $message = '';
        try {
            $this->command($this->getSvnCommand('bob', 'cp -m "stuff" https://reverse-proxy/svnplugin/svn-project-01/sample/trunk https://reverse-proxy/svnplugin/svn-project-01/sample/branches/v1'));
        } catch (\Exception $e) {
            $message = $e->getMessage();
            if (preg_match('/Access to \'.*\' forbidden/', $e->getMessage())) {
                $got_exception = true;
            }
        }
        $this->assertTrue($got_exception, "Message: " . $message);
    }

    public function testWriteAccessGrantedToAlice()
    {
        $got_exception = false;
        try {
            //$this->command($this->getSvnCommand('alice', 'cp -m "stuff" https://reverse-proxy/svnplugin/svn-project-01/sample/trunk https://reverse-proxy/svnplugin/svn-project-01/sample/branches/v1'));
            $this->command($this->getSvnCommand('alice', 'rm -m "Clean" https://reverse-proxy/svnplugin/svn-project-01/sample/branches/v1'));
        } catch (\Exception $e) {
            if (preg_match('/Access to \'.*\' forbidden/', $e->getMessage())) {
                $got_exception = true;
            }
        }
        $this->assertFalse($got_exception);
    }

    private function getWCRevision(): int
    {
        $xml = $this->getXML('svn --xml info');
        return (int) $xml->entry['revision'];
    }

    private function getXML(string $command): SimpleXMLElement
    {
        $xml = simplexml_load_string($this->command($command));
        return $xml;
    }

    private function command(string $command): string
    {
        $total_stdout = '';
        $total_stderr = '';
        $descriptorspec = array(
            0 => array("pipe", "r"),   // stdin is a pipe that the child will read from
            1 => array("pipe", "w"),   // stdout is a pipe that the child will write to
            2 => array("pipe", "w")    // stderr is a pipe that the child will write to
        );
        flush();
        $process = proc_open($command, $descriptorspec, $pipes);
        if (is_resource($process)) {
            fclose($pipes[0]);
            // xor is used to get lines from stdout AND stderr
            while ($stdout = fgets($pipes[1]) xor $stderr = fgets($pipes[2])) {
                $total_stdout .= $stdout;
                $total_stderr .= $stderr;
            }
            fclose($pipes[1]);
            fclose($pipes[2]);
            $return_value = proc_close($process);
            if ($return_value !== 0) {
                throw new \Exception("$command return code: " . $return_value . " " . $total_stderr);
            }
        }
        return $total_stdout;
    }
}
