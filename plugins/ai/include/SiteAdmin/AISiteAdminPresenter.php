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
 *
 */

declare(strict_types=1);

namespace Tuleap\AI\SiteAdmin;

use Tuleap\CSRFSynchronizerTokenPresenter;

/**
 * @psalm-immutable
 */
final class AISiteAdminPresenter
{
    public const string TEMPLATE      = 'site-admin';
    public const string API_KEY_INPUT = 'api_key';

    public string $api_key_input = self::API_KEY_INPUT;

    private function __construct(
        public CSRFSynchronizerTokenPresenter $csrf_token,
        public bool $connection_established,
        public string $api_error,
        public bool $missing_key,
        public bool $authentication_failure,
    ) {
    }

    public function getPageTitle(): string
    {
        return dgettext('tuleap-ai', 'AI Connectors');
    }

    public function getTemplateName(): string
    {
        return self::TEMPLATE;
    }

    public static function buildSuccess(CSRFSynchronizerTokenPresenter $csrf_token): self
    {
        return new self($csrf_token, true, '', false, false);
    }

    public static function buildFailure(CSRFSynchronizerTokenPresenter $csrf_token, string $api_error): self
    {
        return new self($csrf_token, false, $api_error, false, false);
    }

    public static function buildMissingKey(CSRFSynchronizerTokenPresenter $csrf_token): self
    {
        return new self($csrf_token, false, '', true, false);
    }

    public static function buildAuthenticationFailure(CSRFSynchronizerTokenPresenter $csrf_token): self
    {
        return new self($csrf_token, false, '', false, true);
    }
}
