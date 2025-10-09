<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\TEEContainer;

use PasswordHandlerFactory;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Tuleap\BuildVersion\FlavorFinderFromLicense;
use Tuleap\BuildVersion\VersionPresenter;
use Tuleap\SeatManagement\CachedLicenseBuilder;
use TuleapCfg\Command\Docker\DataPersistence;
use TuleapCfg\Command\Docker\PluginsInstallClosureBuilder;
use TuleapCfg\Command\Docker\Postfix;
use TuleapCfg\Command\Docker\SSHDaemon;
use TuleapCfg\Command\Docker\Supervisord;
use TuleapCfg\Command\Docker\Tuleap;
use TuleapCfg\Command\Docker\VariableProviderInterface;
use TuleapCfg\Command\ProcessFactory;
use TuleapCfg\Command\SetupMysql\ConnectionManager;
use TuleapCfg\Command\SetupMysql\DatabaseConfigurator;

final class StartContainerCommand extends Command
{
    private const array PERSISTENT_DATA = [
        '/etc/pki/tls/private/localhost.key.pem',
        '/etc/pki/tls/certs/localhost.cert.pem',
        '/etc/tuleap',
        '/root/.tuleap_passwd',
        '/var/lib/gitolite',
        '/var/lib/tuleap',
    ];

    private const string OPTION_NO_SUPERVISORD = 'no-supervisord';
    private const string OPTION_EXEC           = 'exec';
    private const string OPTION_DEBUG          = 'debug';

    private DataPersistence $data_persistence;

    public function __construct(
        private readonly ProcessFactory $process_factory,
        private readonly PluginsInstallClosureBuilder $plugins_install_closure_builder,
        private readonly VariableProviderInterface $variable_provider,
    ) {
        $this->data_persistence = new DataPersistence(
            $this->process_factory,
            new SSHDaemon($this->process_factory),
            ...self::PERSISTENT_DATA,
        );

        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->setName('run')
            ->setDescription('Run Tuleap Enterprise Edition Docker container')
            ->addOption(self::OPTION_NO_SUPERVISORD, '', InputOption::VALUE_NONE, 'Do not run supervisord at the end of the setup')
            ->addOption(self::OPTION_EXEC, '', InputOption::VALUE_REQUIRED, 'Select a command to run inside the container, before supervisord (if any)')
            ->addOption(self::OPTION_DEBUG, '', InputOption::VALUE_NONE, 'If something is failing, container will hang, available for debug');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $version_presenter = VersionPresenter::fromFlavorFinder(new FlavorFinderFromLicense(CachedLicenseBuilder::instance()));
        $output->writeln(sprintf('<info>Start init sequence for %s</info>', $version_presenter->getFullDescriptiveVersion()));
        try {
            $tuleap      = new Tuleap($this->process_factory, new DatabaseConfigurator(PasswordHandlerFactory::getPasswordHandler(), new ConnectionManager()));
            $tuleap_fqdn = $tuleap->setupOrUpdate(
                new SymfonyStyle($input, $output),
                $this->data_persistence,
                $this->variable_provider,
                $this->plugins_install_closure_builder->buildClosureToInstallPlugins(),
            );

            $console_logger = new ConsoleLogger($output, [LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL]);

            $postfix = new Postfix($this->process_factory);
            $postfix->setup($output, $tuleap_fqdn);

            $supervisord = new Supervisord();
            $supervisord->configure($output);

            $option_exec = $input->getOption(self::OPTION_EXEC);
            if ($option_exec !== null && is_string($option_exec)) {
                $this->exec($console_logger, $option_exec);
            }

            if ($input->getOption(self::OPTION_NO_SUPERVISORD) !== true) {
                $supervisord->run($output);
            }
            return Command::SUCCESS;
        } catch (\Exception $exception) {
            $output->writeln(sprintf('<error>%s</error>', OutputFormatter::escape($exception->getMessage())));
            if ($input->getOption(self::OPTION_DEBUG)) {
                if (Process::isTtySupported()) {
                    $output->writeln('Something went wrong, here is a shell to debug: ');
                    pcntl_exec('/bin/bash');
                    $output->writeln('exec of bash failed');
                } else {
                    $output->writeln('Something went wrong, lets keep the container hanging around for debug');
                    pcntl_exec('/usr/bin/supervisord', ['--nodaemon', '--configuration', '/etc/supervisord.conf']);
                }
            }
        }
        return Command::FAILURE;
    }

    private function exec(LoggerInterface $logger, string $command): void
    {
        $logger->info("Execute command `$command`");
        $process = Process::fromShellCommandline($command, '/');
        $process->setTimeout(0);
        $process->mustRun(function (string $type, string $cmd_output) use ($logger) {
            if ($type == Process::ERR) {
                $logger->error($cmd_output);
            } else {
                $logger->info($cmd_output);
            }
        });
    }
}
