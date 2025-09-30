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

use Tuleap\Cryptography\ConcealedString;

/**
 * This class holds the Database configuration parameters that are not hold by ForgeConfig/DBConfig.
 *
 * They are only used at database initialization.
 *
 * @psalm-immutable
 */
final class DBSetupParameters
{
    public string $azure_prefix                  = '';
    public string $grant_hostname                = '%';
    public ?string $tuleap_fqdn                  = null;
    public ?ConcealedString $site_admin_password = null;

    private function __construct(public string $admin_user, public string $admin_password)
    {
    }

    public static function fromAdminCredentials(string $admin_user, string $admin_password): self
    {
        return new self($admin_user, $admin_password);
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
}
