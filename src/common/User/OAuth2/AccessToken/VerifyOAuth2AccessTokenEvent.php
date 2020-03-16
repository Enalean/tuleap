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

namespace Tuleap\User\OAuth2\AccessToken;

use PFUser;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Event\Dispatchable;

final class VerifyOAuth2AccessTokenEvent implements Dispatchable
{
    public const NAME = 'verifyAccessToken';

    /**
     * @var SplitToken
     *
     * @psalm-readonly
     */
    private $access_token;
    /**
     * @var AuthenticationScope
     *
     * @psalm-readonly
     */
    private $required_scope;
    /**
     * @var PFUser|null
     */
    private $user;

    /**
     * @psalm-param AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier> $required_scope
     */
    public function __construct(SplitToken $access_token, AuthenticationScope $required_scope)
    {
        $this->access_token   = $access_token;
        $this->required_scope = $required_scope;
    }

    /**
     * @psalm-mutation-free
     */
    public function getAccessToken(): SplitToken
    {
        return $this->access_token;
    }

    /**
     * @psalm-mutation-free
     */
    public function getRequiredScope(): AuthenticationScope
    {
        return $this->required_scope;
    }

    public function setVerifiedUser(PFUser $user): void
    {
        $this->user = $user;
    }

    public function getUser(): PFUser
    {
        if ($this->user === null) {
            throw new OAuth2AccessTokenNotFoundException($this->access_token->getID());
        }
        return $this->user;
    }
}
