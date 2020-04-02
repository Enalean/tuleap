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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use TuleapCfg\Command\Docker\DataPersistence;
use TuleapCfg\Command\Docker\Postfix;
use TuleapCfg\Command\Docker\Rsyslog;
use TuleapCfg\Command\Docker\SSHDaemon;
use TuleapCfg\Command\Docker\Supervisord;
use TuleapCfg\Command\Docker\Tuleap;

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
    /**
     * @var DataPersistence
     */
    private $data_persistence;

    public function __construct(ProcessFactory $process_factory)
    {
        $this->process_factory = $process_factory;
        $this->data_persistence = new DataPersistence($this->process_factory, ...self::PERSISTENT_DATA);

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('docker:tuleap-aio-run')
            ->setDescription('Run Tuleap in the context of `tuleap-aio` image');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $tuleap = new Tuleap($this->process_factory);
            if (! $this->data_persistence->isThereAnyData()) {
                $this->installTuleap($output, $tuleap);
                $this->data_persistence->store($output);
                $this->data_persistence->restore($output);
            } else {
                $this->data_persistence->restore($output);

                $this->deployMysqldConfig();
                $mysql_daemon = $this->startMysqlDaemon();
                $tuleap->update($output);
                $this->shutdownMysql($output, $mysql_daemon);
            }
            $rsyslog = new Rsyslog();
            $rsyslog->setup($output);

            $postfix = new Postfix();
            $postfix->setup($output);

            $supervisord = new Supervisord(...Supervisord::UNITS);
            $supervisord->run($output);
        } catch (\Exception $exception) {
            $output->writeln(sprintf('<error>%s</error>', OutputFormatter::escape($exception->getMessage())));
            $output->writeln('Something went wrong, here is a shell to debug: ');
            $return = pcntl_exec('/bin/bash');
            if ($return !== null) {
                throw new \RuntimeException('Exec of /usr/bin/supervisord failed');
            }
        }
        return 0;
    }

    private function installTuleap(OutputInterface $output, Tuleap $tuleap): void
    {
        $ssh_daemon = new SSHDaemon($this->process_factory);

        $mysql_daemon = $this->initializeMysqlDataStore($output);
        $ssh_daemon->startDaemon($output);
        $tuleap->setup($output, 'tuleap.local', 'localhost', 'root', (string) getenv('MYSQL_ROOT_PASSWORD'), 'localhost');
        $this->shutdownMysql($output, $mysql_daemon);
        $ssh_daemon->shutdownDaemon($output);
    }

    private function initializeMysqlDataStore(OutputInterface $output): Process
    {
        $output->writeln('Initialize Mysql data store');
        $this->deployMysqldConfig();
        $this->process_factory->getProcess(['sudo', '-u', 'mysql', '/usr/bin/scl', 'enable', 'rh-mysql57', '--', '/opt/rh/rh-mysql57/root/usr/libexec/mysql-check-socket'])->mustRun();
        $this->process_factory->getProcess(['sudo', '-u', 'mysql', '/usr/bin/scl', 'enable', 'rh-mysql57', '--', '/opt/rh/rh-mysql57/root/usr/libexec/mysqld', '--initialize-insecure', '--datadir=/var/opt/rh/rh-mysql57/lib/mysql', '--user=mysql'])->mustRun();
        file_put_contents('/var/opt/rh/rh-mysql57/lib/mysql/mysql_upgrade_info', '5.7.24');
        chown('/var/opt/rh/rh-mysql57/lib/mysql/mysql_upgrade_info', 'mysql');
        chgrp('/var/opt/rh/rh-mysql57/lib/mysql/mysql_upgrade_info', 'mysql');

        $mysqld = $this->startMysqlDaemon();

        $this->process_factory->getProcess(['scl', 'enable', 'rh-mysql57', '--', 'mysqladmin', '-uroot', 'password', (string) getenv('MYSQL_ROOT_PASSWORD')])->mustRun();
        return $mysqld;
    }

    private function startMysqlDaemon(): Process
    {
        $mysqld = $this->process_factory->getProcess(['sudo', '-u', 'mysql', '/opt/rh/rh-mysql57/root/usr/libexec/mysqld-scl-helper', 'enable', 'rh-mysql57', '--', '/opt/rh/rh-mysql57/root/usr/libexec/mysqld', '--basedir=/opt/rh/rh-mysql57/root/usr', '--pid-file=/var/run/rh-mysql57-mysqld/mysqld.pid']);
        $mysqld->start();
        sleep(1);
        return $mysqld;
    }

    private function deployMysqldConfig(): void
    {
        unlink('/etc/opt/rh/rh-mysql57/my.cnf.d/rh-mysql57-mysql-server.cnf');
        copy(__DIR__ . '/../../../tools/docker/tuleap-aio-c7/rh-mysql57-mysql-server.cnf', '/etc/opt/rh/rh-mysql57/my.cnf.d/rh-mysql57-mysql-server.cnf');
    }

    private function shutdownMysql(OutputInterface $output, Process $mysql_daemon): void
    {
        $output->writeln('Shutdown Mysql');
        $this->process_factory->getProcess(['scl', 'enable', 'rh-mysql57', '--', 'mysqladmin', '-uroot', '-p' . (string) getenv('MYSQL_ROOT_PASSWORD'), 'shutdown'])->mustRun();
        while ($mysql_daemon->isRunning()) {
            $output->writeln('Wait for mysql to shutdown');
            sleep(1);
        }
    }
}
