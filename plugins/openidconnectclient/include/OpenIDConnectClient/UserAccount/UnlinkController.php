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
 *
 */

namespace Tuleap\OpenIDConnectClient\UserAccount;

use CSRFSynchronizerToken;
use Feedback;
use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\OpenIDConnectClient\Provider\ProviderManager;
use Tuleap\OpenIDConnectClient\Provider\ProviderNotFoundException;
use Tuleap\OpenIDConnectClient\UserMapping\UserMappingDataAccessException;
use Tuleap\OpenIDConnectClient\UserMapping\UserMappingManager;
use Tuleap\OpenIDConnectClient\UserMapping\UserMappingNotFoundException;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;

final class UnlinkController implements DispatchableWithRequest
{
    /**
     * @var ProviderManager
     */
    private $provider_manager;

    /**
     * @var UserMappingManager
     */
    private $user_mapping_manager;
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(
        CSRFSynchronizerToken $csrf_token,
        ProviderManager $provider_manager,
        UserMappingManager $user_mapping_manager
    ) {
        $this->csrf_token           = $csrf_token;
        $this->provider_manager     = $provider_manager;
        $this->user_mapping_manager = $user_mapping_manager;
    }

    /**
     * @inheritDoc
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $user = $request->getCurrentUser();
        if ($user->isAnonymous()) {
            throw new ForbiddenException();
        }

        $this->csrf_token->check(OIDCProvidersController::URL);

        $user_mapping_id = $request->get('provider_to_unlink');
        if (! $user_mapping_id) {
            throw new ForbiddenException();
        }

        try {
            $user_mapping = $this->user_mapping_manager->getById($user_mapping_id);
        } catch (UserMappingNotFoundException $ex) {
            $this->redirectToAccountPage(
                $layout,
                dgettext('tuleap-openidconnectclient', 'Request seems invalid, please retry'),
                Feedback::ERROR
            );
        }
        try {
            $provider = $this->provider_manager->getById($user_mapping->getProviderId());
        } catch (ProviderNotFoundException $ex) {
            $this->redirectToAccountPage(
                $layout,
                dgettext('tuleap-openidconnectclient', 'Request seems invalid, please retry'),
                Feedback::ERROR
            );
        }

        if ($provider->isUniqueAuthenticationEndpoint()) {
            $this->redirectToAccountPage(
                $layout,
                sprintf(dgettext('tuleap-openidconnectclient', 'An error occurred while removing the link with %1$s.'), $provider->getName()),
                Feedback::ERROR
            );
        }

        try {
            $this->user_mapping_manager->remove($user_mapping);
            $this->redirectToAccountPage(
                $layout,
                sprintf(dgettext('tuleap-openidconnectclient', 'The link with %1$s have been removed.'), $provider->getName()),
                Feedback::INFO
            );
        } catch (UserMappingDataAccessException $ex) {
            $this->redirectToAccountPage(
                $layout,
                sprintf(dgettext('tuleap-openidconnectclient', 'An error occurred while removing the link with %1$s.'), $provider->getName()),
                Feedback::ERROR
            );
        }
    }

    /**
     * @psalm-return never-return
     */
    private function redirectToAccountPage(BaseLayout $layout, string $message, string $feedback_type): void
    {
        $layout->addFeedback(
            $feedback_type,
            $message
        );
        $layout->redirect(OIDCProvidersController::URL);
        exit();
    }
}
