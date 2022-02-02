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


namespace TuleapCfg\Command\SetupMysql;

use ForgeConfig;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\DB\DBConfig;

/**
 * @psalm-immutable
 */
final class DBSetupParameters
{
    public int $port = DBConfig::DEFAULT_MYSQL_PORT;
    /**
     * @psalm-var value-of<ConnectionManagerInterface::ALLOWED_SSL_MODES>
     */
    public string $ssl_mode                      = ConnectionManagerInterface::SSL_NO_SSL;
    public string $ca_path                       = DBConfig::DEFAULT_MYSQL_CA_FILE_PATH;
    public string $dbname                        = DBConfig::DEFAULT_MYSQL_TULEAP_DB_NAME;
    public string $tuleap_user                   = DBConfig::DEFAULT_MYSQL_TULEAP_USER_NAME;
    public string $azure_prefix                  = '';
    public string $grant_hostname                = '%';
    public ?string $tuleap_password              = null;
    public ?string $tuleap_fqdn                  = null;
    public ?ConcealedString $site_admin_password = null;

    public function __construct(public string $host, public string $admin_user, public string $admin_password)
    {
    }

    /**
     * @throws MissingMandatoryParameterException
     */
    public static function fromForgeConfig(string $admin_user, string $admin_password, ConcealedString $site_admin_password, string $tuleap_fqdn): self
    {
        $host = ForgeConfig::get(DBConfig::CONF_HOST);
        if (! $host) {
            throw new MissingMandatoryParameterException(DBConfig::CONF_HOST);
        }
        $params = new self(ForgeConfig::get(DBConfig::CONF_HOST), $admin_user, $admin_password);
        if (ForgeConfig::get(DBConfig::CONF_ENABLE_SSL)) {
            $params = $params->withSSL(
                (bool) ForgeConfig::get(DBConfig::CONF_SSL_VERIFY_CERT),
                ForgeConfig::get(DBConfig::CONF_SSL_CA),
            );
        }

        $tuleap_password = ForgeConfig::get(DBConfig::CONF_DBPASSWORD);
        if (! $tuleap_password) {
            throw new MissingMandatoryParameterException(DBConfig::CONF_DBPASSWORD);
        }

        return $params
            ->withPort(ForgeConfig::getInt(DBConfig::CONF_PORT))
            ->withTuleapCredentials(
                ForgeConfig::get(DBConfig::CONF_DBNAME),
                ForgeConfig::get(DBConfig::CONF_DBUSER),
                ForgeConfig::get(DBConfig::CONF_DBPASSWORD),
            )
            ->withSiteAdminPassword($site_admin_password)
            ->withTuleapFQDN($tuleap_fqdn);
    }

    public function withPort(int $port): self
    {
        $new       = clone $this;
        $new->port = $port;
        return $new;
    }

    public function withSSL(bool $verify_cert, string $ca_path): self
    {
        $new           = clone $this;
        $new->ssl_mode = $verify_cert ? ConnectionManagerInterface::SSL_VERIFY_CA : ConnectionManagerInterface::SSL_NO_VERIFY;
        $new->ca_path  = $ca_path;
        return $new;
    }

    public function withSSLCaFile(string $file): self
    {
        $new          = clone $this;
        $new->ca_path = $file;
        return $new;
    }

    /**
     * @psalm-param value-of<ConnectionManagerInterface::ALLOWED_SSL_MODES> $ssl_mode
     */
    public function withSSLMode(string $ssl_mode): self
    {
        $new           = clone $this;
        $new->ssl_mode = $ssl_mode;
        return $new;
    }

    public function withTuleapCredentials(string $dbname, string $username, string $password): self
    {
        $new                  = clone $this;
        $new->dbname          = $dbname;
        $new->tuleap_user     = $username;
        $new->tuleap_password = $password;
        return $new;
    }

    public function withSiteAdminPassword(?ConcealedString $password): self
    {
        $new = clone $this;
        if ($password !== null) {
            $new->site_admin_password = clone $password;
        }
        return $new;
    }

    public function withAzurePrefix(string $azure): self
    {
        $new               = clone $this;
        $new->azure_prefix = $azure;
        return $new;
    }

    public function withTuleapFQDN(?string $fqdn): self
    {
        $new = clone $this;
        if ($fqdn !== null) {
            $new->tuleap_fqdn = $fqdn;
        }
        return $new;
    }

    public function withGrantHostname(string $grant): self
    {
        $new                 = clone $this;
        $new->grant_hostname = $grant;
        return $new;
    }

    /**
     * @psalm-assert-if-true !null $this->site_admin_password
     * @psalm-assert-if-true !null $this->tuleap_fqdn
     */
    public function canSetup(): bool
    {
        return isset($this->site_admin_password, $this->tuleap_fqdn);
    }

    /**
     * @psalm-assert-if-true !null $this->tuleap_user
     * @psalm-assert-if-true !null $this->tuleap_password
     */
    public function hasTuleapCredentials(): bool
    {
        return isset($this->tuleap_user, $this->tuleap_password);
    }
}
