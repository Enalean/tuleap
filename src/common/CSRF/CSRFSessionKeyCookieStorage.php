<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\CSRF;

use Tuleap\CookieManager;

final readonly class CSRFSessionKeyCookieStorage implements CSRFSessionKeyStorage
{
    private const COOKIE_NAME = 'csrf_session_key';

    public function __construct(private CookieManager $cookie_manager)
    {
    }

    public function getSessionKey(): string
    {
        $session_key = $this->cookie_manager->getCookie(self::COOKIE_NAME);
        if ($session_key === null) {
            $session_key = sodium_bin2base64(random_bytes(32), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
            $this->cookie_manager->setCookie(self::COOKIE_NAME, $session_key);
        }
        return $session_key;
    }

    public function clearStorage(): void
    {
        $this->cookie_manager->removeCookie(self::COOKIE_NAME);
    }
}
