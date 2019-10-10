<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace TuleapCfg\Command;

use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Tuleap\System\ServiceControl;
use TuleapCfg\Command\Configure\ConfigureApache;

class DockerAioRunCommand extends Command
{
    private const PERSISTENT_DATA = [
        '/etc/pki/tls/private/localhost.key.pem',
        '/etc/pki/tls/certs/localhost.cert.pem',
        '/etc/tuleap',
        '/etc/ssh/ssh_host_ecdsa_key',
        '/etc/ssh/ssh_host_ed25519_key',
        '/etc/ssh/ssh_host_ecdsa_key.pub',
        '/etc/ssh/ssh_host_ed25519_key.pub',
        '/etc/ssh/ssh_host_rsa_key',
        '/etc/ssh/ssh_host_rsa_key.pub',
        '/root/.tuleap_passwd',
        '/var/lib/gitolite',
        '/var/lib/tuleap',
        '/var/opt/rh/rh-mysql57/lib/mysql',
    ];

    /**
     * @var ProcessFactory
     */
    private $process_factory;

    public function __construct(ProcessFactory $process_factory)
    {
        $this->process_factory = $process_factory;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('docker:tuleap-aio-run')
            ->setDescription('Run Tuleap in the context of `tuleap-aio` image');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->generateSSHServerKeys();
            if (! is_dir('/data/var/lib/tuleap')) {
                $this->installTuleap($output);
                $this->movePersistentData($output);
                $this->restorePersistentData($output);
            } else {
                $this->restorePersistentData($output);
                $this->deployMysqldConfig();
                $this->deployCrondConfig();
                $this->regenerateConfigurations($output);
                $mysql_daemon = $this->startMysqlDaemon();
                $this->runForgeUpgrade($output);
                $this->queueSystemCheck($output);
                $this->shutdownMysql($output, $mysql_daemon);
            }
            $this->setupRsyslog($output);
            $this->setupSupervisord($output);
            $this->setupPostfix($output);
            $this->runTuleap($output);
        } catch (\Exception $exception) {
            $output->writeln(sprintf('<error>%s</error>', OutputFormatter::escape($exception->getMessage())));
            $output->writeln("Something went wrong, here is a shell to debug: ");
            $return = pcntl_exec('/bin/bash');
            if ($return !== null) {
                throw new \RuntimeException('Exec of /usr/bin/supervisord failed');
            }
        }
    }

    private function generateSSHServerKeys()
    {
        if (! is_file('/etc/ssh/ssh_host_ecdsa_key')) {
            $process = $this->process_factory->getProcess(['/usr/sbin/sshd-keygen']);
            $process->mustRun();
        }
    }

    private function installTuleap(OutputInterface $output)
    {
        $mysql_daemon = $this->initializeMysqlDataStore($output);
        $ssh_daemon = $this->startSSHDaemon($output);
        $this->setupTuleap($output);
        $this->shutdownMysql($output, $mysql_daemon);
        $this->shutdownSSHDaemon($output, $ssh_daemon);
    }

    private function initializeMysqlDataStore(OutputInterface $output): Process
    {
        $output->writeln("Initialize Mysql data store");
        $this->deployMysqldConfig();
        $this->process_factory->getProcess(['sudo', '-u', 'mysql', '/usr/bin/scl', 'enable', 'rh-mysql57', '--', '/opt/rh/rh-mysql57/root/usr/libexec/mysql-check-socket'])->mustRun();
        $this->process_factory->getProcess(['sudo', '-u', 'mysql', '/usr/bin/scl', 'enable', 'rh-mysql57', '--', '/opt/rh/rh-mysql57/root/usr/libexec/mysqld', '--initialize-insecure', '--datadir=/var/opt/rh/rh-mysql57/lib/mysql', '--user=mysql'])->mustRun();
        file_put_contents('/var/opt/rh/rh-mysql57/lib/mysql/mysql_upgrade_info', '5.7.24');
        chown('/var/opt/rh/rh-mysql57/lib/mysql/mysql_upgrade_info', 'mysql');
        chgrp('/var/opt/rh/rh-mysql57/lib/mysql/mysql_upgrade_info', 'mysql');

        $mysqld = $this->startMysqlDaemon();

        $this->process_factory->getProcess(['scl', 'enable', 'rh-mysql57', '--', 'mysqladmin', '-uroot', 'password', getenv('MYSQL_ROOT_PASSWORD')])->mustRun();
        return $mysqld;
    }

    private function startMysqlDaemon(): Process
    {
        $mysqld = $this->process_factory->getProcess(['sudo', '-u', 'mysql', '/opt/rh/rh-mysql57/root/usr/libexec/mysqld-scl-helper', 'enable', 'rh-mysql57', '--', '/opt/rh/rh-mysql57/root/usr/libexec/mysqld', '--basedir=/opt/rh/rh-mysql57/root/usr', '--pid-file=/var/run/rh-mysql57-mysqld/mysqld.pid']);
        $mysqld->start();
        sleep(1);
        return $mysqld;
    }

    private function deployMysqldConfig()
    {
        unlink('/etc/opt/rh/rh-mysql57/my.cnf.d/rh-mysql57-mysql-server.cnf');
        copy(__DIR__.'/../../../tools/docker/tuleap-aio-c7/rh-mysql57-mysql-server.cnf', '/etc/opt/rh/rh-mysql57/my.cnf.d/rh-mysql57-mysql-server.cnf');
    }

    private function startSSHDaemon(OutputInterface $output): Process
    {
        $output->writeln("Start SSH Daemon");
        $process = $this->process_factory->getProcess(['/usr/sbin/sshd', '-E', '/dev/stderr', '-D']);
        $process->start();
        return $process;
    }

    private function setupTuleap(OutputInterface $output)
    {
        $output->writeln("Install Tuleap");
        $this->process_factory->getProcess(['/bin/bash', '+x', '/usr/share/tuleap/tools/setup.el7.sh', '--assumeyes', '--configure', '--server-name=tuleap.local', '--mysql-server=localhost', '--mysql-password='.getenv('MYSQL_ROOT_PASSWORD')])->mustRun();
        $this->process_factory->getProcess(['/usr/bin/tuleap', 'config-set', ServiceControl::FORGECONFIG_INIT_MODE, ServiceControl::SUPERVISORD])->mustRun();
    }

    private function shutdownMysql(OutputInterface $output, Process $mysql_daemon): void
    {
        $output->writeln("Shutdown Mysql");
        $this->process_factory->getProcess(['scl', 'enable', 'rh-mysql57', '--', 'mysqladmin', '-uroot', '-p'.getenv('MYSQL_ROOT_PASSWORD'), 'shutdown'])->mustRun();
        while ($mysql_daemon->isRunning()) {
            $output->writeln("Wait for mysql to shutdown");
            sleep(1);
        }
    }

    private function shutdownSSHDaemon(OutputInterface $output, Process $process): void
    {
        $output->writeln("Shutdown SSH Daemon");
        $process->stop(0, SIGTERM);
    }

    private function runTuleap(OutputInterface $output): void
    {
        $output->writeln("Let the place for Supervisord");
        $return = pcntl_exec('/usr/bin/supervisord', ['--nodaemon', '--configuration', '/etc/supervisord.conf']);
        if ($return !== null) {
            throw new \RuntimeException('Exec of /usr/bin/supervisord failed');
        }
    }

    /**
     * @see https://www.projectatomic.io/blog/2014/09/running-syslog-within-a-docker-container/
     *      https://github.com/rsyslog/rsyslog-docker/blob/master/base/centos7/Dockerfile
     */
    private function setupRsyslog(OutputInterface $output)
    {
        $output->writeln("Setup Rsyslog");
        unlink('/etc/rsyslog.d/listen.conf');
        unlink('/etc/rsyslog.conf');
        copy(__DIR__.'/../../../tools/docker/tuleap-aio-c7/rsyslog.conf', '/etc/rsyslog.conf');
    }

    private function setupSupervisord(OutputInterface $output) : void
    {
        $output->writeln("Setup Supervisord");
        foreach (new \DirectoryIterator(__DIR__.'/../../../tools/docker/tuleap-aio-c7/supervisor.d') as $file) {
            assert($file instanceof SplFileInfo);
            if (! $file->isDir()) {
                copy($file->getPathname(), '/etc/supervisord.d/'.$file->getFilename());
            }
        }
        file_put_contents(
            '/etc/supervisord.d/supervisord-server-credentials.ini',
            $this->generateCredentialsConfigurationForSupervisordSocket()
        );
    }

    private function generateCredentialsConfigurationForSupervisordSocket() : string
    {
        $password = sodium_bin2hex(random_bytes(32));
        return <<<EOT
                [unix_http_server]
                username = tuleap
                password = $password
                EOT;
    }

    private function setupPostfix(OutputInterface $output)
    {
        $output->writeln("Setup Postfix");
        $file_path = '/etc/postfix/main.cf';
        $content = file_get_contents($file_path);
        $new_content = preg_replace('/^inet_interfaces = localhost$/m', 'inet_interfaces = all', $content);
        file_put_contents($file_path, $new_content);
    }

    private function movePersistentData(OutputInterface $output): void
    {
        if (! is_dir('/data') && ! mkdir('/data', 0755) && ! is_dir('/data')) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', '/data'));
        }
        foreach (self::PERSISTENT_DATA as $path) {
            $output->writeln("Move $path to persistent storage");
            $this->createBaseDir($path);
            $this->process_factory->getProcess(['/bin/mv', $path, '/data'.$path])->mustRun();
        }
    }

    private function createBaseDir(string $path): void
    {
        $dirname = '/data'.dirname($path);
        if (! is_dir($dirname) && ! mkdir($dirname, 0755, true) && ! is_dir($dirname)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dirname));
        }
    }

    private function restorePersistentData(OutputInterface $output)
    {
        foreach (self::PERSISTENT_DATA as $path) {
            if (is_link($path)) {
                continue;
            }
            if (is_dir($path)) {
                $this->process_factory->getProcess(['/bin/rm', '-rf', $path])->mustRun();
            }
            if (is_file($path)) {
                unlink($path);
            }
            $output->writeln("Create link to persistent storage for $path");
            symlink('/data'.$path, $path);
        }
    }

    private function regenerateConfigurations(OutputInterface $output)
    {
        $output->writeln("Regenerate configurations for nginx, fpm");
        $this->process_factory->getProcess([__DIR__.'/../../../tools/utils/php73/run.php', '--module=nginx,fpm'])->mustRun();

        $output->writeln("Regenerate configuration for apache");
        $configure_apache = new ConfigureApache('/');
        $configure_apache->configure();
    }

    private function deployCrondConfig()
    {
        copy(__DIR__.'/../../utils/cron.d/codendi', '/etc/cron.d/tuleap');
    }

    private function runForgeUpgrade(OutputInterface $output)
    {
        $output->writeln('<info>Run forgeupgrade</info>');
        $this->process_factory->getProcess(['/usr/lib/forgeupgrade/bin/forgeupgrade', '--config=/etc/tuleap/forgeupgrade/config.ini', 'update'])->setTimeout(0)->mustRun();
    }

    private function queueSystemCheck(OutputInterface $output)
    {
        $output->writeln('<info>Queue a system check</info>');
        $this->process_factory->getProcess(['/usr/bin/tuleap', 'queue-system-check'])->mustRun();
    }
}
