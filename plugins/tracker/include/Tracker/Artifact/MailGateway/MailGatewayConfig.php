<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\MailGateway;

class MailGatewayConfig
{
    public const DISABLED = 'disabled';
    public const TOKEN    = 'token';
    public const INSECURE = 'insecure';

    private string $cache_emailgateway_mode;

    public function __construct(
        private MailGatewayConfigDao $dao,
    ) {
    }

    public function setEmailgatewayMode(string $emailgateway_mode): void
    {
        if ($this->isAllowedEmailgatewayMode($emailgateway_mode)) {
            $this->cache_emailgateway_mode = $emailgateway_mode;
            $this->dao->save($emailgateway_mode);
        }
    }

    public function isInsecureEmailgatewayEnabled(): bool
    {
        return $this->getEmailgatewayRowMode() === self::INSECURE;
    }

    public function isTokenBasedEmailgatewayEnabled(): bool
    {
        return $this->getEmailgatewayRowMode() === self::TOKEN;
    }

    public function getEmailgatewayRowMode(): string
    {
        if (! isset($this->cache_emailgateway_mode)) {
            $this->cache_emailgateway_mode = self::DISABLED;
            $row                           = $this->dao->searchEmailgatewayConfiguration();
            if ($row && $this->isAllowedEmailgatewayMode($row['value'])) {
                $this->cache_emailgateway_mode = $row['value'];
            }
        }

        return $this->cache_emailgateway_mode;
    }

    private function isAllowedEmailgatewayMode(string $emailgateway_mode): bool
    {
        return in_array($emailgateway_mode, [self::DISABLED, self::TOKEN, self::INSECURE]);
    }
}
