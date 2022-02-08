<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

namespace Tuleap\JWT\Generators;

use Lcobucci\JWT\Configuration;
use Tuleap\Project\UGroupLiteralizer;
use UserManager;

class JWTGenerator
{
    /** @var UserManager */
    private $user_manager;

    /** @var UGroupLiteralizer */
    private $ugroup_literalizer;
    /**
     * @var Configuration
     */
    private $jwt_configuration;

    public function __construct(Configuration $jwt_configuration, UserManager $user_manager, UGroupLiteralizer $ugroup_literalizer)
    {
        $this->jwt_configuration  = $jwt_configuration;
        $this->user_manager       = $user_manager;
        $this->ugroup_literalizer = $ugroup_literalizer;
    }

    /**
     * Generate a json web token
     * for the current user
     */
    public function getToken(): string
    {
        $current_user = $this->user_manager->getCurrentUser();
        $data         = [
            'user_id'     => (int) $current_user->getId(),
            'user_rights' => $this->ugroup_literalizer->getUserGroupsForUserWithArobase($current_user),
        ];

        $token = $this->jwt_configuration->builder()
            ->withClaim('data', $data)
            ->expiresAt($this->getExpireDate())
            ->getToken($this->jwt_configuration->signer(), $this->jwt_configuration->signingKey());

        return $token->toString();
    }

    private function getExpireDate(): \DateTimeImmutable
    {
        $issuedAt  = new \DateTimeImmutable();
        $notBefore = $issuedAt;
        return $notBefore->modify('+30 minutes');
    }
}
