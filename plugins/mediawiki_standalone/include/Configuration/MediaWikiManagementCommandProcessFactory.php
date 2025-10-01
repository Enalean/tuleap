<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Configuration;

use Psr\Log\LoggerInterface;
use Tuleap\DB\DBConfig;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class MediaWikiManagementCommandProcessFactory implements MediaWikiManagementCommandFactory
{
    private const string LOCAL_SETTINGS_FILE_MANAGED_BY_MEDIAWIKI = 'LocalSettings.php';

    public function __construct(private LoggerInterface $logger, private string $path_setting_directory)
    {
    }

    #[\Override]
    public function buildInstallCommand(): MediaWikiManagementCommand
    {
        if (file_exists($this->path_setting_directory . '/' . self::LOCAL_SETTINGS_FILE_MANAGED_BY_MEDIAWIKI)) {
            $this->logger->debug('MediaWiki standalone farm instance is already installed');
            return new MediaWikiManagementCommandDoNothing();
        }

        return new MediaWikiManagementCommandProcess(
            $this->logger,
            LocalSettingsRepresentation::MEDIAWIKI_PHP_CLI
                . ' /usr/share/mediawiki-tuleap-flavor/current-lts/maintenance/install.php'
                . ' --confpath "${:CONFPATH}"'
                . ' --dbserver "${:DBSERVER}"'
                . ' --dbname plugin_mediawiki_standalone_farm'
                . ' --dbuser "${:DBUSER}"'
                . ' --dbpass "${:DBPASS}"'
                . ' --pass "${:PASS}"'
                . ' TuleapFarmManagement tuleap_mediawikifarm_admin',
            [
                'CONFPATH' => $this->path_setting_directory,
                'DBSERVER' => \ForgeConfig::get(DBConfig::CONF_HOST) . ':' . \ForgeConfig::getInt(DBConfig::CONF_PORT),
                'DBUSER' => \ForgeConfig::get(DBConfig::CONF_DBUSER),
                'DBPASS' => new ConcealedString(\ForgeConfig::get(DBConfig::CONF_DBPASSWORD)),
                'PASS' => new ConcealedString(base64_encode(random_bytes(32))),
            ],
        );
    }

    #[\Override]
    public function buildFarmInstanceConfigurationUpdate(): MediaWikiManagementCommand
    {
        $farm_instance_configuration_path =  $this->path_setting_directory . '/' . self::LOCAL_SETTINGS_FILE_MANAGED_BY_MEDIAWIKI;
        return new MediaWikiManagementCommandCallable(
            function () use ($farm_instance_configuration_path): Ok|Err {
                try {
                    $configuration_content = \Psl\File\read($farm_instance_configuration_path);
                } catch (\Psl\File\Exception\InvalidArgumentException | \Psl\File\Exception\RuntimeException $ex) {
                    return Result::err(
                        new MediaWikiManagementCommandFailure(
                            1,
                            'Read configuration file ' . $farm_instance_configuration_path,
                            $ex->getMessage(),
                        )
                    );
                }
                $updated_configuration_content = \Psl\Regex\replace($configuration_content, "/^wfLoadSkin\( '(?!Vector).+' \);$/m", '//\0');
                try {
                    \Psl\File\write($farm_instance_configuration_path, $updated_configuration_content);
                } catch (\Psl\File\Exception\InvalidArgumentException | \Psl\File\Exception\RuntimeException $ex) {
                    return Result::err(
                        new MediaWikiManagementCommandFailure(
                            1,
                            'Update configuration file ' . $farm_instance_configuration_path,
                            $ex->getMessage(),
                        )
                    );
                }

                return Result::ok(null);
            }
        );
    }

    #[\Override]
    public function buildUpdateFarmInstanceCommand(): MediaWikiManagementCommand
    {
        return new MediaWikiManagementCommandProcess(
            $this->logger,
            LocalSettingsRepresentation::MEDIAWIKI_PHP_CLI . ' /usr/share/mediawiki-tuleap-flavor/current-lts/maintenance/update.php --quick'
        );
    }

    #[\Override]
    public function buildUpdateProjectInstanceCommand(string $project_name): MediaWikiManagementCommand
    {
        return new MediaWikiManagementCommandProcess(
            $this->logger,
            LocalSettingsRepresentation::MEDIAWIKI_PHP_CLI
                . ' /usr/share/mediawiki-tuleap-flavor/current-lts/maintenance/update.php --quick'
                . ' --sfr "${:PROJECT}"',
            ['PROJECT' => $project_name],
        );
    }

    #[\Override]
    public function buildUpdateToMediaWiki135ProjectInstanceCommand(string $project_name): MediaWikiManagementCommand
    {
        return new MediaWikiManagementCommandProcess(
            $this->logger,
            LocalSettingsRepresentation::MEDIAWIKI_PHP_CLI
                . ' /usr/share/mediawiki-tuleap-flavor/1.35/extensions/TuleapWikiFarm/maintenance/migrateInstance.php'
                . ' --skip-registration --projectname "${:PROJECT}"',
            ['PROJECT' => $project_name],
        );
    }
}
