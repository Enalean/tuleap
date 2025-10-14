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

interface FPMSessionInterface
{
    public const string DEPLOYED_FILE_NAME = 'tuleap_sessions.part';

    public function deployFreshTuleapConf(LoggerInterface $logger, string $tuleap_php_configuration_folder, string $php_configuration_folder): void;

    public function forceDeployFreshTuleapConf(LoggerInterface $logger, string $tuleap_php_configuration_folder, string $php_configuration_folder): void;
}
