<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\OAuth2Server\AuthorizationServer;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Layout\BaseLayout;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\OAuth2ServerCore\AuthorizationServer\AuthorizationEndpointController;
use Tuleap\OAuth2ServerCore\AuthorizationServer\ConsentRequiredResponseBuilder;

final class OAuth2ConsentRequiredResponseBuilder implements ConsentRequiredResponseBuilder
{
    public function __construct(private AuthorizationFormRenderer $form_renderer)
    {
    }

    /**
     * @psalm-param non-empty-list<\Tuleap\Authentication\Scope\AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>> $scopes
     */
    #[\Override]
    public function buildConsentRequiredResponse(
        ServerRequestInterface $request,
        OAuth2App $client_app,
        string $redirect_uri,
        ?string $state_value,
        ?string $code_challenge,
        ?string $oidc_nonce,
        array $scopes,
    ): ResponseInterface {
        $layout = $request->getAttribute(BaseLayout::class);
        assert($layout instanceof BaseLayout);
        $csrf_token = new \CSRFSynchronizerToken(AuthorizationEndpointController::CSRF_TOKEN);
        $data       = new AuthorizationFormData($client_app, $csrf_token, $redirect_uri, $state_value, $code_challenge, $oidc_nonce, ...$scopes);
        return $this->form_renderer->renderForm($data, $layout)
            ->withHeader(
                'Content-Security-Policy',
                "default-src 'report-sample'; base-uri 'none'; script-src 'self' 'unsafe-inline' 'report-sample'; style-src 'self' 'report-sample'; font-src 'self'; img-src 'self'; connect-src 'self'; manifest-src 'self';"
                . "form-action 'self' " . $redirect_uri . ';'
                . "frame-ancestors 'none'; block-all-mixed-content; report-uri /csp-violation;"
            );
    }
}
