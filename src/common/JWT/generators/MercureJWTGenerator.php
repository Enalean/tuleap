<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\JWT\generators;

use Lcobucci\JWT\Configuration;
use Tuleap\User\REST\UserRepresentation;
use UserManager;

class MercureJWTGenerator
{
    public function __construct(
        private Configuration $jwt_configuration,
        private UserManager $user_manager,
    ) {
    }
    /**
     * Generate a json web token
     * for the current user
     */
    public function getTokenWithSubscription(string $app_name, int $id): string
    {
        return $this->getToken($app_name, $id, true);
    }

    public function getTokenWithoutSubscription(string $app_name, int $id): string
    {
        return $this->getToken($app_name, $id, false);
    }

    private function getToken(string $app_name, int $id, bool $enable_subscription): string
    {
        $current_user = $this->user_manager->getCurrentUser();
        $app_list     = [
            $app_name . '/' . $id,
            $app_name . '/' . $id . '/{component}/{id}',
        ];
        if ($enable_subscription) {
            $app_list[] = '/.well-known/mercure/subscriptions/' . urlencode($app_name . '/' . $id) . '{/sub}';
            $app_list[] = '/.well-known/mercure/subscriptions/' . urlencode($app_name . '/' . $id) . '{subsubscription}{/sub}';
        }
        $mercure = [
            'subscribe' => $app_list,
            'payload' => UserRepresentation::build($current_user),
        ];
        $token   = $this->jwt_configuration->builder()
            ->withClaim('mercure', $mercure)
            ->expiresAt($this->getExpireDate())
            ->getToken($this->jwt_configuration->signer(), $this->jwt_configuration->signingKey());

        return $token->toString();
    }

    /**
     * @return string Generate a token for the backend.
     */
    public function getTokenBackend(): string
    {
        $mercure = [
            'publish' => ['*'],
            'subscribe' => [''],
            'payload' => [''],
        ];
        $token   = $this->jwt_configuration->builder()
            ->withClaim('mercure', $mercure)
            ->expiresAt($this->getExpireDate())
            ->getToken($this->jwt_configuration->signer(), $this->jwt_configuration->signingKey());

        return $token->toString();
    }
    private function getExpireDate(): \DateTimeImmutable
    {
        return (new \DateTimeImmutable())->modify('+30 minutes');
    }
}
