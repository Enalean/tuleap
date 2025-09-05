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
use Tuleap\Cryptography\ConcealedString;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\REST\UserRepresentation;

final class MercureJWTGeneratorImpl implements MercureJWTGenerator
{
    public function __construct(
        private Configuration $jwt_configuration,
        private readonly ProvideUserAvatarUrl $provide_user_avatar_url,
    ) {
    }

    #[\Override]
    public function getTokenWithSubscription(string $app_name, int $id, \PFUser $user): ConcealedString
    {
        return $this->getToken($app_name, $id, true, $user);
    }

    #[\Override]
    public function getTokenWithoutSubscription(string $app_name, int $id, \PFUser $user): ConcealedString
    {
        return $this->getToken($app_name, $id, false, $user);
    }

    private function getToken(string $app_name, int $id, bool $enable_subscription, \PFUser $user): ConcealedString
    {
        $app_list = [
            $app_name . '/' . $id,
            $app_name . '/' . $id . '/{component}/{id}',
        ];
        if ($enable_subscription) {
            $app_list[] = '/.well-known/mercure/subscriptions/' . urlencode($app_name . '/' . $id) . '{/sub}';
            $app_list[] = '/.well-known/mercure/subscriptions/' . urlencode($app_name . '/' . $id) . '{subsubscription}{/sub}';
        }
        $mercure = [
            'subscribe' => $app_list,
            'payload' => UserRepresentation::build($user, $this->provide_user_avatar_url),
        ];
        $token   = $this->jwt_configuration->builder()
            ->withClaim('mercure', $mercure)
            ->expiresAt($this->getExpireDate())
            ->getToken($this->jwt_configuration->signer(), $this->jwt_configuration->signingKey());

        return new ConcealedString($token->toString());
    }

    #[\Override]
    public function getTokenBackend(): ConcealedString
    {
        $mercure = [
            'publish' => ['*'],
            'subscribe' => [''],
            'payload' => [''],
        ];
        $token   = $this->jwt_configuration->builder()
            ->withClaim('mercure', $mercure)
            ->expiresAt($this->getExpireDateBackend())
            ->getToken($this->jwt_configuration->signer(), $this->jwt_configuration->signingKey());

        return new ConcealedString($token->toString());
    }

    private function getExpireDate(): \DateTimeImmutable
    {
        return (new \DateTimeImmutable())->modify('+2 minutes');
    }

    private function getExpireDateBackend(): \DateTimeImmutable
    {
        return (new \DateTimeImmutable())->modify('+5 seconds');
    }
}
