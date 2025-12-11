#!/usr/share/tuleap/src/utils/php-launcher.sh
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
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use Tuleap\DB\DBFactory;
use Tuleap\ForgeUpgrade\ForgeUpgrade;
use TuleapCfg\Command\Docker\VariableProviderFromEnvironment;
use TuleapCfg\Command\ProcessFactory;

$application = new Application();
$application->addCommand(new \TuleapCfg\Command\ConfigureCommand());
$process_factory   = new ProcessFactory();
$variable_provider = new VariableProviderFromEnvironment();
$application->addCommand(new \TuleapCfg\Command\SystemControlCommand($process_factory));
$application->addCommand(new \TuleapCfg\Command\StartCommunityEditionContainerCommand(
    $process_factory,
    new \TuleapCfg\Command\Docker\PluginsInstallClosureBuilderFromVariable($variable_provider, $process_factory),
    $variable_provider,
));
$application->addCommand(new \TuleapCfg\Command\SetupMysqlInitCommand(
    new \TuleapCfg\Command\SetupMysql\DatabaseConfigurator(
        PasswordHandlerFactory::getPasswordHandler(),
        new \TuleapCfg\Command\SetupMysql\ConnectionManager(),
    ),
));
$application->addCommand(new \TuleapCfg\Command\SetupTuleapCommand(
    $process_factory,
    new \Tuleap\Cryptography\KeyFactoryFromFileSystem(),
    static fn (\Psr\Log\LoggerInterface $logger) => new ForgeUpgrade(
        DBFactory::getMainTuleapDBConnection()->getDB()->getPdo(),
        $logger,
        new \Tuleap\DB\DatabaseUUIDV7Factory(),
    ),
));
$application->addCommand(new \TuleapCfg\Command\SetupForgeUpgradeCommand());
$application->addCommand(new \TuleapCfg\Command\SiteDeploy\SiteDeployCommand());
$application->addCommand(new \TuleapCfg\Command\SiteDeploy\Images\SiteDeployImagesCommand());
$application->addCommand(new \TuleapCfg\Command\SiteDeploy\FPM\SiteDeployFPMCommand());
$application->addCommand(new \TuleapCfg\Command\SiteDeploy\Gitolite3\SiteDeployGitolite3Command($process_factory));
$application->addCommand(new \TuleapCfg\Command\SiteDeploy\Gitolite3\SiteDeployGitolite3HooksCommand());
$application->addCommand(new \TuleapCfg\Command\SiteDeploy\Nginx\SiteDeployNginxCommand());
$application->addCommand(new \TuleapCfg\Command\SiteDeploy\Apache\SiteDeployApacheCommand());
$application->addCommand(new \TuleapCfg\Command\SiteDeploy\ForgeUpgrade\SiteDeployForgeUpgradeCommand());
$application->addCommand(new TuleapCfg\Command\SiteDeploy\Realtime\SiteDeployRealtimeCommand());
$application->addCommand(new \TuleapCfg\Command\SiteDeploy\Locale\SiteDeployLocaleGenerationCommand());
$application->addCommand(new \TuleapCfg\Command\SiteDeploy\Plugins\SiteDeployPluginsCommand(new ProcessFactory()));
$application->run();
