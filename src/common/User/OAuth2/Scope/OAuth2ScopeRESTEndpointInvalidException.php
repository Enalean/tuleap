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

namespace Tuleap\User\OAuth2\Scope;

use Luracast\Restler\Data\ApiMethodInfo;
use Tuleap\Authentication\Scope\AuthenticationScopeIdentifier;
use Tuleap\User\OAuth2\OAuth2Exception;

final class OAuth2ScopeRESTEndpointInvalidException extends \LogicException implements OAuth2Exception
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function invalidIdentifierKey(ApiMethodInfo $api_method_info, InvalidOAuth2ScopeIdentifierException $exception): self
    {
        return new self($exception->getMessage() . ' ' . self::buildMessageOriginOfTheIssue($api_method_info));
    }

    public static function scopeNotFound(ApiMethodInfo $api_method_info, AuthenticationScopeIdentifier $identifier): self
    {
        return new self('Scope ' . $identifier->toString() . ' does not exist. ' .
            self::buildMessageOriginOfTheIssue($api_method_info));
    }

    private static function buildMessageOriginOfTheIssue(ApiMethodInfo $api_method_info): string
    {
        return 'Source of the issue: ' . $api_method_info->className . '::' . $api_method_info->methodName;
    }
}
