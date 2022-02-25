<?php
/*
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

namespace TuleapCfg\Command\SetupTuleap;

use Tuleap\Config\ConfigKeyLegacyBool;
use Tuleap\Config\ConfigSerializer;
use Tuleap\Config\ConfigurationVariables;
use Tuleap\ServerHostname;

final class SetupTuleap
{
    public function __construct(private string $base_directory = '/')
    {
    }

    public function setup(string $fqdn): void
    {
        \ForgeConfig::set(ServerHostname::DEFAULT_DOMAIN, $fqdn);
        \ForgeConfig::set(ServerHostname::LIST_HOST, 'lists.' . $fqdn);
        \ForgeConfig::set(ServerHostname::FULL_NAME, $fqdn);
        \ForgeConfig::set(ConfigurationVariables::EMAIL_ADMIN, 'codendi-admin@' . $fqdn);
        \ForgeConfig::set(ConfigurationVariables::EMAIL_CONTACT, 'codendi-contact@' . $fqdn);
        \ForgeConfig::set(ConfigurationVariables::NOREPLY, sprintf('"Tuleap" <noreply@%s>', $fqdn));
        \ForgeConfig::set(ConfigurationVariables::ORG_NAME, 'Tuleap');
        \ForgeConfig::set(ConfigurationVariables::LONG_ORG_NAME, 'Tuleap');
        \ForgeConfig::set(ConfigurationVariables::HOMEDIR_PREFIX, '');
        \ForgeConfig::set(ConfigurationVariables::GRPDIR_PREFIX, '');
        \ForgeConfig::set(ConfigurationVariables::MAIL_SECURE_MODE, ConfigKeyLegacyBool::FALSE);
        \ForgeConfig::set(ConfigurationVariables::DISABLE_SUBDOMAINS, ConfigKeyLegacyBool::TRUE);

        (new ConfigSerializer())->save(
            $this->base_directory . '/etc/tuleap/conf/local.inc',
            0640,
            'root',
            'codendiadm',
            ServerHostname::class,
            ConfigurationVariables::class
        );
    }
}
