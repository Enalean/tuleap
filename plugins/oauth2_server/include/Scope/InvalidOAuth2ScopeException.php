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
 */

declare(strict_types=1);

namespace Tuleap\OAuth2Server\Scope;

use Throwable;
use Tuleap\Authentication\Scope\AuthenticationScopeIdentifier;
use Tuleap\OAuth2Server\OAuth2ServerException;
use Tuleap\User\OAuth2\Scope\InvalidOAuth2ScopeIdentifierException;

final class InvalidOAuth2ScopeException extends \RuntimeException implements OAuth2ServerException
{
    private function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public static function invalidFormat(InvalidOAuth2ScopeIdentifierException $ex): self
    {
        return new self('Scope cannot exist: ' . $ex->getMessage(), $ex);
    }

    public static function scopeDoesNotExist(AuthenticationScopeIdentifier $scope_identifier): self
    {
        return new self(
            sprintf(
                'The scope %s does not exist (some scopes are only available when a plugin is present)',
                $scope_identifier->toString()
            )
        );
    }
}
