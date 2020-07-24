<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\MailGateway;

class MailGatewayConfig
{

    private static $DISABLED = 'disabled';
    private static $TOKEN    = 'token';
    private static $INSECURE = 'insecure';

    /** @var MailGatewayConfigDao */
    private $dao;

    /** @var string */
    private $cache_emailgateway_mode;

    public function __construct(MailGatewayConfigDao $dao)
    {
        $this->dao = $dao;
    }

    public function setEmailgatewayMode($emailgateway_mode)
    {
        if ($this->isAllowedEmailgatewayMode($emailgateway_mode)) {
            $this->cache_emailgateway_mode = $emailgateway_mode;
            return $this->dao->save($emailgateway_mode);
        }
    }

    public function isEmailgatewayDisabled()
    {
        return $this->getEmailgatewayMode() === self::$DISABLED;
    }

    public function isInsecureEmailgatewayEnabled()
    {
        return $this->getEmailgatewayMode() === self::$INSECURE;
    }

    public function isTokenBasedEmailgatewayEnabled()
    {
        return $this->getEmailgatewayMode() === self::$TOKEN;
    }

    private function getEmailgatewayMode()
    {
        if (! isset($this->cache_emailgateway_mode)) {
            $this->cache_emailgateway_mode = self::$DISABLED;
            $row = $this->dao->searchEmailgatewayConfiguration()->getRow();
            if ($row && $this->isAllowedEmailgatewayMode($row['value'])) {
                $this->cache_emailgateway_mode = $row['value'];
            }
        }

        return $this->cache_emailgateway_mode;
    }

    private function isAllowedEmailgatewayMode($emailgateway_mode)
    {
        return in_array($emailgateway_mode, [self::$DISABLED, self::$TOKEN, self::$INSECURE]);
    }
}
