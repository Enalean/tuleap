<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\User\Profile;

use ForgeConfig;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Option\Option;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\ServerHostname;
use Tuleap\User\Avatar\AvatarHashStorage;
use Tuleap\User\Avatar\ComputeAvatarHash;
use Tuleap\User\ProvideCurrentRequestUser;
use UserManager;

final class AvatarController extends DispatchablePSR15Compatible implements DispatchableWithRequestNoAuthz
{
    private const string DEFAULT_AVATAR   = __DIR__ . '/../../../www/themes/common/images/avatar_default.png';
    private const int ONE_YEAR_IN_SECONDS = 3600 * 24 * 365;

    public function __construct(
        EmitterInterface $emitter,
        private readonly BinaryFileResponseBuilder $binary_file_response_builder,
        private readonly ResponseFactoryInterface $response_factory,
        private readonly ProvideCurrentRequestUser $current_request_user_provider,
        private readonly UserManager $user_manager,
        private readonly AvatarGenerator $avatar_generator,
        private readonly AvatarHashStorage $avatar_hash_storage,
        private readonly ComputeAvatarHash $compute_avatar_hash,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $current_user = $this->current_request_user_provider->getCurrentRequestUser($request);
        // Avatar is a public information for all authenticated users
        if (! ForgeConfig::areAnonymousAllowed() && ($current_user === null || $current_user->isAnonymous())) {
            return $this->getDefaultAvatarErrorResponse($request);
        }

        $user_name = (string) $request->getAttribute('name');
        $user      = $this->user_manager->getUserByUserName($user_name);

        if ($user === null || ! $user->hasAvatar()) {
            return $this->getDefaultAvatarErrorResponse($request);
        }

        $user_avatar_path = $user->getAvatarFilePath();
        if (! is_file($user_avatar_path)) {
            $this->avatar_generator->generate($user, $user_avatar_path);
            $user->setHasCustomAvatar(false);
            $this->user_manager->updateDb($user);
        }

        $hash = (string) $request->getAttribute('hash');

        return $this->avatar_hash_storage
            ->retrieve($user)
            ->orElse(
                /** @return Option<string> */
                fn(): Option => Option::fromValue($this->compute_avatar_hash->computeAvatarHash($user_avatar_path))
            )->mapOr(
                function (string $current_hash) use ($request, $hash, $user): ResponseInterface {
                    if ($current_hash === $hash || $hash === '') {
                        return $this->getUserAvatarResponse($request, $user, $hash);
                    }

                    return $this->response_factory
                        ->createResponse(301)
                        ->withHeader(
                            'Location',
                            ServerHostname::HTTPSUrl() . '/users/' . urlencode($user->getUserName()) . '/avatar-' . urlencode($current_hash) . '.png'
                        );
                },
                $this->getDefaultAvatarErrorResponse($request)
            );
    }

    private function getDefaultAvatarErrorResponse(ServerRequestInterface $request): ResponseInterface
    {
        return $this->binary_file_response_builder->fromFilePath(
            $request,
            self::DEFAULT_AVATAR,
            'default-avatar.png',
            'image/png'
        )
            ->withStatus(404)
            ->withHeader('Cache-Control', 'max-age=60');
    }

    private function getUserAvatarResponse(ServerRequestInterface $request, \PFUser $user, string $hash): ResponseInterface
    {
        $response = $this->binary_file_response_builder->fromFilePath(
            $request,
            $user->getAvatarFilePath(),
            sprintf('avatar-%s-%s.png', $user->getUserName(), $hash),
            'image/png'
        );

        if ($hash === '') {
            return $response->withHeader('Cache-Control', 'max-age=60');
        }

        return $response->withHeader('Cache-Control', 'max-age=' . self::ONE_YEAR_IN_SECONDS . ',immutable');
    }
}
