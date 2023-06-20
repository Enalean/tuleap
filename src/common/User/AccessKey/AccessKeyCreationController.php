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

declare(strict_types=1);

namespace Tuleap\User\AccessKey;

use DateTimeImmutable;
use HTTPRequest;
use Tuleap\Authentication\Scope\AggregateAuthenticationScopeBuilder;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Layout\BaseLayout;
use Tuleap\NeverThrow\Result;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeBuilderCollector;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeDAO;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeIdentifier;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeSaver;
use Tuleap\User\AccessKey\Scope\CoreAccessKeyScopeBuilderFactory;
use Tuleap\User\AccessKey\Scope\InvalidScopeIdentifierKeyException;
use Tuleap\User\AccessKey\Scope\NoValidAccessKeyScopeException;
use Tuleap\User\Account\DisplayKeysTokensController;
use Tuleap\WebAuthn\Authentication\WebAuthnAuthentication;

class AccessKeyCreationController implements DispatchableWithRequest
{
    /**
     * @var \CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(
        \CSRFSynchronizerToken $csrf_token,
        private readonly WebAuthnAuthentication $web_authn_authentication,
    ) {
        $this->csrf_token = $csrf_token;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $current_user = $request->getCurrentUser();
        if ($current_user->isAnonymous()) {
            throw new ForbiddenException(_('Unauthorized action for anonymous'));
        }

        $this->csrf_token->check(DisplayKeysTokensController::URL);

        $result = $this->web_authn_authentication->checkKeyResult(
            $current_user,
            $request->get('webauthn_result') ?: ''
        );
        if (Result::isErr($result)) {
            $layout->addFeedback(\Feedback::ERROR, (string) $result->error);
            $layout->redirect(DisplayKeysTokensController::URL);
        }

        $access_key_creator = new AccessKeyCreator(
            new LastAccessKeyIdentifierStore(
                new PrefixedSplitTokenSerializer(new PrefixAccessKey()),
                (new KeyFactory())->getEncryptionKey(),
                $_SESSION
            ),
            new AccessKeyDAO(),
            new SplitTokenVerificationStringHasher(),
            new AccessKeyScopeSaver(new AccessKeyScopeDAO()),
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
            new AccessKeyCreationNotifier(\Tuleap\ServerHostname::HTTPSUrl(), \Codendi_HTMLPurifier::instance())
        );

        $description     = $request->get('access-key-description') ?: '';
        $expiration_date = $this->getExpirationDate($request, $layout);

        try {
            $access_key_creator->create(
                $current_user,
                $description,
                $expiration_date,
                ...$this->getAccessKeyScopes($request, $layout)
            );
            $layout->redirect(DisplayKeysTokensController::URL);
        } catch (AccessKeyAlreadyExpiredException $exception) {
            $layout->addFeedback(
                \Feedback::ERROR,
                _("You cannot create an already expired access key.")
            );

            $layout->redirect(DisplayKeysTokensController::URL);
        } catch (NoValidAccessKeyScopeException $exception) {
            $this->rejectMalformedAccessKeyScopes($layout);
        }
    }

    /**
     * @return AuthenticationScope[]
     */
    private function getAccessKeyScopes(HTTPRequest $request, BaseLayout $layout): array
    {
        $access_key_scope_builder = AggregateAuthenticationScopeBuilder::fromBuildersList(
            CoreAccessKeyScopeBuilderFactory::buildCoreAccessKeyScopeBuilder(),
            AggregateAuthenticationScopeBuilder::fromEventDispatcher(\EventManager::instance(), new AccessKeyScopeBuilderCollector())
        );

        $scope_identifier_keys = $request->get('access-key-scopes');
        if (! is_array($scope_identifier_keys)) {
            $this->rejectMalformedAccessKeyScopes($layout);
        }

        $access_key_scopes = [];

        foreach ($scope_identifier_keys as $scope_identifier_key) {
            try {
                $access_key_scope_identifier = AccessKeyScopeIdentifier::fromIdentifierKey($scope_identifier_key);
            } catch (InvalidScopeIdentifierKeyException $ex) {
                $this->rejectMalformedAccessKeyScopes($layout);
            }
            $access_key_scope = $access_key_scope_builder->buildAuthenticationScopeFromScopeIdentifier(
                $access_key_scope_identifier
            );
            if ($access_key_scope === null) {
                $this->rejectMalformedAccessKeyScopes($layout);
            }
            $access_key_scopes[] = $access_key_scope;
        }

        return $access_key_scopes;
    }

    /**
     * @psalm-return never-return
     */
    private function rejectMalformedAccessKeyScopes(BaseLayout $layout): void
    {
        $layout->addFeedback(
            \Feedback::ERROR,
            _('Access key scopes are not well formed.')
        );

        $layout->redirect(DisplayKeysTokensController::URL);
    }

    private function getExpirationDate(HTTPRequest $request, BaseLayout $layout): ?DateTimeImmutable
    {
        $expiration_date = null;

        $provided_expiration_date = $request->get('access-key-expiration-date');
        if ($provided_expiration_date !== '') {
            $expiration_date = \DateTimeImmutable::createFromFormat('Y-m-d', $provided_expiration_date);

            if (! $expiration_date) {
                $layout->addFeedback(
                    \Feedback::ERROR,
                    _("Expiration date is not well formed.")
                );

                $layout->redirect(DisplayKeysTokensController::URL);
            }

            $expiration_date = $expiration_date->setTime(23, 59, 59);
        }

        return $expiration_date;
    }
}
