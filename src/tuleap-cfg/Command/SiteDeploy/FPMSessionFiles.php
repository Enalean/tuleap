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

namespace TuleapCfg\Command\SiteDeploy;

use Psr\Log\LoggerInterface;

final class FPMSessionFiles implements FPMSessionInterface
{
    private const TULEAP_CONF_FILE = 'tuleap_sessions_files.part';

    public function deployFreshTuleapConf(LoggerInterface $logger, string $tuleap_php_configuration_folder, string $php_configuration_folder): void
    {
        $src_file = $tuleap_php_configuration_folder . '/' . self::TULEAP_CONF_FILE;
        $dst_file = $php_configuration_folder . '/php-fpm.d/' . self::DEPLOYED_FILE_NAME;

        if (file_exists($dst_file)) {
            return;
        }

        $logger->info("Deploy $src_file into $dst_file");
        copy($src_file, $dst_file);
    }

    public function forceDeployFreshTuleapConf(
        LoggerInterface $logger,
        string $tuleap_php_configuration_folder,
        string $php_configuration_folder
    ): void {
        $src_file = $tuleap_php_configuration_folder . '/' . self::TULEAP_CONF_FILE;
        $dst_file = $php_configuration_folder . '/php-fpm.d/' . self::DEPLOYED_FILE_NAME;

        $logger->info("Deploy $src_file into $dst_file");
        copy($src_file, $dst_file);
    }
}
