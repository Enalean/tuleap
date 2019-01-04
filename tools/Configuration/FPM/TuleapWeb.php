<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

namespace Tuleap\Configuration\FPM;

use Tuleap\Configuration\Logger\LoggerInterface;
use Tuleap\Configuration\Logger\Wrapper;

class TuleapWeb
{

    private $application_user;
    private $logger;
    private $development;
    private $php_configuration_folder;
    private $tuleap_php_configuration_folder;
    /**
     * @var array
     */
    private $previous_php_configuration_folders;

    public function __construct(
        LoggerInterface $logger,
        $application_user,
        $development,
        $php_configuration_folder,
        $tuleap_php_configuration_folder,
        array $previous_php_configuration_folders
    ) {
        $this->logger                             = new Wrapper($logger, 'fpm');
        $this->application_user                   = $application_user;
        $this->development                        = $development;
        $this->php_configuration_folder           = $php_configuration_folder;
        $this->tuleap_php_configuration_folder    = $tuleap_php_configuration_folder;
        $this->previous_php_configuration_folders = $previous_php_configuration_folders;
    }

    public static function buildForPHP72(
        LoggerInterface $logger,
        $application_user,
        $development
    ) {
        return new self(
            $logger,
            $application_user,
            $development,
            '/etc/opt/remi/php72',
            '/usr/share/tuleap/src/etc/fpm72',
            []
        );
    }

    public function configure()
    {
        $this->logger->info("Start configuration in $this->php_configuration_folder/php-fpm.d/");
        if (file_exists("$this->php_configuration_folder/php-fpm.d/www.conf") &&
                filesize("$this->php_configuration_folder/php-fpm.d/www.conf") !== 0) {
            $this->logger->info("Backup $this->php_configuration_folder/php-fpm.d/www.conf");
            rename("$this->php_configuration_folder/php-fpm.d/www.conf", "$this->php_configuration_folder/php-fpm.d/www.conf.orig");
            touch("$this->php_configuration_folder/php-fpm.d/www.conf");
        }
        if (! file_exists("$this->php_configuration_folder/php-fpm.d/tuleap.conf")) {
            $this->moveExistingConfigurationFromOldConfigurationFolders();
        }
        if (! file_exists("$this->php_configuration_folder/php-fpm.d/tuleap.conf")) {
            $this->deployFreshTuleapConf();
        }

        if (! is_dir('/var/tmp/tuleap_cache/php/session') || ! is_dir('/var/tmp/tuleap_cache/php/wsdlcache')) {
            $this->logger->info("Create temporary directories");
            $this->createDirectoryForAppUser('/var/tmp/tuleap_cache');
            $this->createDirectoryForAppUser('/var/tmp/tuleap_cache/php');
            $this->createDirectoryForAppUser('/var/tmp/tuleap_cache/php/session');
            $this->createDirectoryForAppUser('/var/tmp/tuleap_cache/php/wsdlcache');
        }

        $this->logger->info("Configuration done!");
    }

    private function createDirectoryForAppUser($path)
    {
        if (! is_dir($path)) {
            mkdir($path, 0700);
        }
        chown($path, $this->application_user);
        chgrp($path, $this->application_user);
    }

    private function moveExistingConfigurationFromOldConfigurationFolders()
    {
        foreach ($this->previous_php_configuration_folders as $previous_php_configuration_folder) {
            if (file_exists("$previous_php_configuration_folder/php-fpm.d/tuleap.conf")) {
                rename("$previous_php_configuration_folder/php-fpm.d/tuleap.conf", "$this->php_configuration_folder/php-fpm.d/tuleap.conf");
                return;
            }
        }
    }

    private function deployFreshTuleapConf()
    {
        $this->logger->info("Deploy $this->php_configuration_folder/php-fpm.d/tuleap.conf");

        $variables = array(
            '%application_user%',
        );
        $replacement = array(
            $this->application_user,
        );

        if ($this->development) {
            $variables[]   = ';php_flag[display_errors] = on';
            $replacement[] = 'php_flag[display_errors] = on';

            $variables[]   = ';php_flag[html_errors] = on';
            $replacement[] = 'php_flag[html_errors] = on';
        }

        $this->replacePlaceHolderInto(
            "$this->tuleap_php_configuration_folder/tuleap.conf",
            "$this->php_configuration_folder/php-fpm.d/tuleap.conf",
            $variables,
            $replacement
        );
    }

    private function replacePlaceHolderInto($template_path, $target_path, array $variables, array $values)
    {
        file_put_contents(
            $target_path,
            str_replace(
                $variables,
                $values,
                file_get_contents(
                    $template_path
                )
            )
        );
    }
}
