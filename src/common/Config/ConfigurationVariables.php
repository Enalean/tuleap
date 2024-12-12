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

namespace Tuleap\Config;

/**
 * This class will hold the configuration variables initially transferred from src/etc/local.inc.dist
 *
 * It's the older sister of ConfigurationVariablesLocalIncDist that contains the "other" variables from local.inc.dist
 * moved during the second batch of transfer.
 * We still need to distinguish the two classes because during init sequence of Tuleap we must be able to set a list of
 * variables (@see \TuleapCfg\Command\SetupTuleap\SetupTuleap) until the init sequence can save values in database.
 */
final class ConfigurationVariables
{
    #[ConfigKey('Email address pointing to the Tuleap site administrators')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString]
    public const EMAIL_ADMIN = 'sys_email_admin';

    #[ConfigKey('Email address pointing to the Tuleap contacts')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString]
    public const EMAIL_CONTACT = 'sys_email_contact';

    #[ConfigKey('Address from which emails are sent')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString]
    public const NOREPLY = 'sys_noreply';

    #[ConfigKey('Name of the instance')]
    #[ConfigKeyHelp('The name of the instance is used in various web pages and mails')]
    #[ConfigKeyString('Tuleap')]
    public const NAME = 'sys_name';

    #[ConfigKey('Company/organization running the system (short)')]
    #[ConfigKeyHelp('Short version or abbreviation commonly used')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString('Tuleap')]
    public const ORG_NAME = 'sys_org_name';

    #[ConfigKey('Company/organization running the system (long)')]
    #[ConfigKeyHelp('Long/official/formal name')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString('Tuleap')]
    public const LONG_ORG_NAME = 'sys_long_org_name';

    #[ConfigKey('When 0 mail sent to everybody can lead to information leak, non projects members can receive mails of private projects')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyLegacyBool(false)]
    public const MAIL_SECURE_MODE = 'sys_mail_secure_mode';

    #[ConfigKey('Disable sub-domains (like svn.proj.example.com)')]
    #[ConfigKeyHelp('This is a legacy configuration variable. Subdomains must be left deactivated')]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyLegacyBool(false)]
    public const DISABLE_SUBDOMAINS = 'sys_disable_subdomains';

    #[ConfigKey('Server timezone')]
    #[ConfigKeyString('Europe/Paris')]
    public const SERVER_TIMEZONE = 'sys_server_timezone';
}
