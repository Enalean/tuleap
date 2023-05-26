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

use Psr\Log\LoggerInterface;
use TuleapCfg\Command\TemplateHelper;

final class SiteDeployFPM
{
    public const PHP81_DST_CONF_DIR = '/etc/opt/remi/php81';
    public const PHP81_SRC_CONF_DIR = __DIR__ . '/../../../../etc/fpm81';

    private const FPM_PART_ERRORS             = 'tuleap_errors.part';
    private const FPM_PART_ERRORS_PROD        = 'tuleap_errors_prod.part';
    private const FPM_PART_ERRORS_DEV         = 'tuleap_errors_dev.part';
    private const FPM_CONFIGURATION_TO_DEPLOY = [
        'tuleap.conf'                      => 'tuleap.conf',
        'tuleap-long-running-request.conf' => 'tuleap-long-running-request.conf',
        'tuleap_common.part'               => 'tuleap_common.part',
    ];

    private const ENV_SESSION        = 'TULEAP_FPM_SESSION_MODE';
    private const SESSION_REDIS      = 'redis';
    private const ENV_REDIS_SERVER   = 'TULEAP_REDIS_SERVER';
    private const ENV_REDIS_PORT     = 'TULEAP_REDIS_PORT';
    private const ENV_REDIS_PASSWORD = 'TULEAP_REDIS_PASSWORD';
    private const ENV_REDIS_TLS      = 'TULEAP_REDIS_USE_TLS';

    /**
     * @var int|string
     */
    private $application_user;
    private $logger;
    private $development;
    private $php_configuration_folder;
    private $tuleap_php_configuration_folder;
    /**
     * @var array
     */
    private $previous_php_configuration_folders;
    /**
     * @var string
     */
    private $temp_base_directory;
    /**
     * @var FPMSessionInterface
     */
    private $session;

    public function __construct(
        LoggerInterface $logger,
        string $application_user,
        bool $development,
        FPMSessionInterface $session,
        string $php_configuration_folder,
        string $tuleap_php_configuration_folder,
        array $previous_php_configuration_folders,
        string $temp_base_directory = '/var/tmp',
    ) {
        $this->logger                             = $logger;
        $this->application_user                   = $application_user;
        $this->development                        = $development;
        $this->session                            = $session;
        $this->php_configuration_folder           = $php_configuration_folder;
        $this->tuleap_php_configuration_folder    = $tuleap_php_configuration_folder;
        $this->previous_php_configuration_folders = $previous_php_configuration_folders;
        $this->temp_base_directory                = $temp_base_directory;
    }

    public static function buildSessionFromEnv(): FPMSessionInterface
    {
        $session_mode = getenv(self::ENV_SESSION);
        if ($session_mode === self::SESSION_REDIS && ($server = getenv(self::ENV_REDIS_SERVER)) !== false) {
            $port = getenv(self::ENV_REDIS_PORT);
            if ($port === false) {
                $port = FPMSessionRedis::DEFAULT_REDIS_PORT;
            } else {
                $port = (int) $port;
            }
            $password = getenv(self::ENV_REDIS_PASSWORD);
            if ($password === false) {
                $password = '';
            }
            $use_tls = getenv(self::ENV_REDIS_TLS) === '1';
            return new FPMSessionRedis(\ForgeConfig::get('redis_config_file'), \ForgeConfig::get('sys_http_user'), $server, $use_tls, $port, $password);
        }
        return new FPMSessionFiles();
    }

    public static function buildForPHP81(
        LoggerInterface $logger,
        string $application_user,
        bool $development,
    ): self {
        return new self(
            $logger,
            $application_user,
            $development,
            self::buildSessionFromEnv(),
            self::PHP81_DST_CONF_DIR,
            self::PHP81_SRC_CONF_DIR,
            []
        );
    }

    public function configure(): void
    {
        $this->logger->info("Start configuration in $this->php_configuration_folder/php-fpm.d/");
        $this->moveDefaultWww();

        foreach ($this->getConfigurationFilesToDeploy() as $reference_file => $fpm_configuration_to_deploy) {
            if (! file_exists("$this->php_configuration_folder/php-fpm.d/$fpm_configuration_to_deploy")) {
                $this->moveExistingConfigurationFromOldConfigurationFolders($fpm_configuration_to_deploy);
            }
            if (! file_exists("$this->php_configuration_folder/php-fpm.d/$fpm_configuration_to_deploy")) {
                $this->deployFreshTuleapConf($reference_file, $fpm_configuration_to_deploy);
            }
        }
        $this->session->deployFreshTuleapConf($this->logger, $this->tuleap_php_configuration_folder, $this->php_configuration_folder);

        $this->createMissingDirectories();

        $this->logger->info("Configuration done!");
    }

    private function getConfigurationFilesToDeploy(): array
    {
        $config_files = self::FPM_CONFIGURATION_TO_DEPLOY;
        if ($this->development) {
            $config_files[self::FPM_PART_ERRORS_DEV] = self::FPM_PART_ERRORS;
        } else {
            $config_files[self::FPM_PART_ERRORS_PROD] = self::FPM_PART_ERRORS;
        }
        return $config_files;
    }

    public function forceDeploy(): void
    {
        $this->moveDefaultWww();
        $this->remoteExistingTuleapParts();

        foreach ($this->getConfigurationFilesToDeploy() as $reference_file => $fpm_configuration_to_deploy) {
            $this->deployFreshTuleapConf($reference_file, $fpm_configuration_to_deploy);
        }
        $this->session->forceDeployFreshTuleapConf($this->logger, $this->tuleap_php_configuration_folder, $this->php_configuration_folder);

        $this->createMissingDirectories();
    }

    private function createDirectoryForAppUser(string $path): void
    {
        if (! is_dir($path)) {
            mkdir($path, 0700);
        }
        chown($path, $this->application_user);
        chgrp($path, $this->application_user);
    }

    private function moveExistingConfigurationFromOldConfigurationFolders(string $configuration_name): void
    {
        foreach ($this->previous_php_configuration_folders as $previous_php_configuration_folder) {
            if (file_exists("$previous_php_configuration_folder/php-fpm.d/$configuration_name")) {
                rename("$previous_php_configuration_folder/php-fpm.d/$configuration_name", "$this->php_configuration_folder/php-fpm.d/$configuration_name");
                return;
            }
        }
    }

    private function deployFreshTuleapConf(string $reference_file, string $configuration_name): void
    {
        $this->logger->info("Deploy $this->tuleap_php_configuration_folder/$reference_file into $this->php_configuration_folder/php-fpm.d/$configuration_name");

        $variables   = [
            '%application_user%',
        ];
        $replacement = [
            $this->application_user,
        ];

        TemplateHelper::replacePlaceHolderInto(
            "$this->tuleap_php_configuration_folder/$reference_file",
            "$this->php_configuration_folder/php-fpm.d/$configuration_name",
            $variables,
            $replacement,
            0640
        );
    }

    private function moveDefaultWww(): void
    {
        if (
            file_exists("$this->php_configuration_folder/php-fpm.d/www.conf") &&
            filesize("$this->php_configuration_folder/php-fpm.d/www.conf") !== 0
        ) {
            $this->logger->info("Backup $this->php_configuration_folder/php-fpm.d/www.conf");
            rename(
                "$this->php_configuration_folder/php-fpm.d/www.conf",
                "$this->php_configuration_folder/php-fpm.d/www.conf.orig"
            );
            touch("$this->php_configuration_folder/php-fpm.d/www.conf");
        }
    }

    private function createMissingDirectories(): void
    {
        if (! is_dir($this->temp_base_directory . '/tuleap_cache/php/session')) {
            $this->logger->info("Create temporary directories");
            $this->createDirectoryForAppUser($this->temp_base_directory . '/tuleap_cache');
            $this->createDirectoryForAppUser($this->temp_base_directory . '/tuleap_cache/php');
            $this->createDirectoryForAppUser($this->temp_base_directory . '/tuleap_cache/php/session');
        }
    }

    private function remoteExistingTuleapParts(): void
    {
        foreach (new \DirectoryIterator($this->php_configuration_folder . '/php-fpm.d') as $item) {
            if (! $item->isDir() && preg_match('/^tuleap_.*\.part/', $item->getBasename())) {
                unlink($item->getPathname());
            }
        }
    }
}
