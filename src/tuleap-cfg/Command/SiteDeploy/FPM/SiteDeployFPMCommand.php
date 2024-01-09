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

namespace TuleapCfg\Command\SiteDeploy\FPM;

use ForgeConfig;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use TuleapCfg\Command\ProcessFactory;

final class SiteDeployFPMCommand extends Command
{
    public const NAME            = 'site-deploy:fpm';
    public const OPT_PHP_VERSION = 'php-version';
    public const PHP81           = 'php81';
    public const PHP82           = 'php82';
    public const OPT_FORCE       = 'force';

    private const OPT_DEVELOPMENT = 'development';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        $this->setDescription('Deploy PHP FPM configuration files')
            ->addOption(self::OPT_PHP_VERSION, '', InputOption::VALUE_REQUIRED, 'Target php version: `php81` (default), `php82`')
            ->addOption(self::OPT_DEVELOPMENT, '', InputOption::VALUE_NONE, 'Deploy development version of the configuration files')
            ->addOption(self::OPT_FORCE, '', InputOption::VALUE_NONE, 'Force files to be rewritten (by default existing files are not modified)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ForgeConfig::loadInSequence();

        $php_version = $input->getOption(self::OPT_PHP_VERSION);
        assert(is_string($php_version));

        $development = $input->getOption(self::OPT_DEVELOPMENT) === true;
        $force       = $input->getOption(self::OPT_FORCE) === true;

        $console_logger = new ConsoleLogger($output, [LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL]);

        if ($php_version === self::PHP81) {
            $deploy = SiteDeployFPM::buildForPHP81(new ProcessFactory(), $console_logger, ForgeConfig::get('sys_http_user'), $development);
        } elseif ($php_version === self::PHP82) {
            $deploy = SiteDeployFPM::buildForPHP82(new ProcessFactory(), $console_logger, ForgeConfig::get('sys_http_user'), $development);
        } else {
            $output->write(
                sprintf(
                    '<error>%s option does not support "%s"</error>',
                    OutputFormatter::escape(self::OPT_PHP_VERSION),
                    OutputFormatter::escape($php_version)
                )
            );
            return self::FAILURE;
        }
        if ($force) {
            $deploy->forceDeploy();
        } else {
            $deploy->configure();
        }

        return 0;
    }
}
