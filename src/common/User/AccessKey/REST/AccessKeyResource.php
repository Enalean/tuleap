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

namespace Tuleap\User\AccessKey\REST;

use DateTime;
use DateTimeImmutable;
use Luracast\Restler\RestException;
use Tuleap\Authentication\Scope\AggregateAuthenticationScopeBuilder;
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\REST\AccessKeyHeaderExtractor;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\User\AccessKey\AccessKeyAlreadyExpiredException;
use Tuleap\User\AccessKey\AccessKeyCreationNotifier;
use Tuleap\User\AccessKey\AccessKeyCreator;
use Tuleap\User\AccessKey\AccessKeyDAO;
use Tuleap\User\AccessKey\AccessKeyMetadataRetriever;
use Tuleap\User\AccessKey\AccessKeyRevoker;
use Tuleap\User\AccessKey\LastAccessKeyIdentifierStore;
use Tuleap\User\AccessKey\PrefixAccessKey;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeBuilderCollector;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeDAO;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeIdentifier;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeRetriever;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeSaver;
use Tuleap\User\AccessKey\Scope\CoreAccessKeyScopeBuilderFactory;
use Tuleap\User\AccessKey\Scope\InvalidScopeIdentifierKeyException;
use Tuleap\User\AccessKey\Scope\NoValidAccessKeyScopeException;

class AccessKeyResource extends AuthenticatedResource
{
    public const ROUTE = 'access_keys';

    /**
     * @url OPTIONS
     * @access protected
     */
    public function options(): void
    {
        Header::allowOptionsPost();
    }

    /**
     * Create a new access key
     *
     * Example of expected format:
     * <pre>
     * {<br/>
     *   "description": "This is my API key",<br/>
     *   "expiration_date": "2019-08-07T10:15:57+02:00",<br/>
     *   "scopes": ["write:rest"]<br/>
     * }<br/>
     * </pre>
     * <br/>
     * The expiration date is optional. If provided, it must be formatted as an ISO-8601 date.<br/>
     * In addition, you cannot create an already expired access key.
     * The scopes are optional, by default only read/write access to the REST API is given.
     *
     * @url POST
     *
     * @access protected
     *
     * @param AccessKeyPOSTRepresentation $access_key The access key representation for creation.
     *
     * @status 201
     *
     * @throws RestException 400
     *
     * @return UserAccessKeyCreationRepresentation
     */
    public function post(AccessKeyPOSTRepresentation $access_key)
    {
        $this->options();

        $current_user                        = \UserManager::instance()->getCurrentUser();
        $storage_access_key_identifier_store = [];
        $last_access_key_identifier_store    = new LastAccessKeyIdentifierStore(
            new PrefixedSplitTokenSerializer(new PrefixAccessKey()),
            (new KeyFactory)->getEncryptionKey(),
            $storage_access_key_identifier_store
        );
        $access_key_creator               = new AccessKeyCreator(
            $last_access_key_identifier_store,
            new AccessKeyDAO(),
            new SplitTokenVerificationStringHasher(),
            new AccessKeyScopeSaver(new AccessKeyScopeDAO()),
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
            new AccessKeyCreationNotifier(\HTTPRequest::instance()->getServerUrl(), \Codendi_HTMLPurifier::instance())
        );

        $expiration_date = null;
        $provided_expiration_date = $access_key->expiration_date;
        if ($provided_expiration_date !== null) {
            $expiration_date = DateTimeImmutable::createFromFormat(DateTime::ATOM, $provided_expiration_date);

            if (! $expiration_date) {
                throw new RestException(400, "Please provide a valid ISO-8601 date for expiration_date");
            }
        }

        $key_scopes               = [];
        $access_key_scope_builder = AggregateAuthenticationScopeBuilder::fromBuildersList(
            CoreAccessKeyScopeBuilderFactory::buildCoreAccessKeyScopeBuilder(),
            AggregateAuthenticationScopeBuilder::fromEventDispatcher(\EventManager::instance(), new AccessKeyScopeBuilderCollector())
        );
        foreach ($access_key->scopes as $scope_identifier) {
            try {
                $key_scope = $access_key_scope_builder->buildAuthenticationScopeFromScopeIdentifier(
                    AccessKeyScopeIdentifier::fromIdentifierKey($scope_identifier)
                );
            } catch (InvalidScopeIdentifierKeyException $exception) {
                throw new RestException(
                    400,
                    "$scope_identifier does not have the expected format of an access key scope identifier"
                );
            }
            if ($key_scope !== null) {
                $key_scopes[] = $key_scope;
            }
        }

        try {
            $access_key_creator->create($current_user, $access_key->description, $expiration_date, ...$key_scopes);
        } catch (AccessKeyAlreadyExpiredException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (NoValidAccessKeyScopeException $exception) {
            throw new RestException(400, 'No valid access key scope identifier has been given');
        }

        $representation = new UserAccessKeyCreationRepresentation();
        $representation->build(
            $last_access_key_identifier_store->getLastGeneratedAccessKeyIdentifier()
        );

        return $representation;
    }

    /**
     * @url OPTIONS {id}
     * @access protected
     * @param string|int $id
     */
    public function optionsId($id): void
    {
        Header::allowOptionsPostDelete();
    }

    /**
     * Get information about an access key
     *
     * @url GET {id}
     *
     * @access protected
     *
     * @param string $id ID of the access key or "self" to get information about the access key currently used
     *
     * @throws RestException 404
     */
    public function get(string $id): UserAccessKeyRepresentation
    {
        $this->optionsId($id);
        $current_user = \UserManager::instance()->getCurrentUser();

        $user_access_key_representation_retriever = new UserAccessKeyRepresentationRetriever(
            new AccessKeyHeaderExtractor(new PrefixedSplitTokenSerializer(new PrefixAccessKey()), $_SERVER),
            new AccessKeyMetadataRetriever(
                new AccessKeyDAO(),
                new AccessKeyScopeRetriever(
                    new AccessKeyScopeDAO(),
                    AggregateAuthenticationScopeBuilder::fromBuildersList(
                        CoreAccessKeyScopeBuilderFactory::buildCoreAccessKeyScopeBuilder(),
                        AggregateAuthenticationScopeBuilder::fromEventDispatcher(\EventManager::instance(), new AccessKeyScopeBuilderCollector())
                    )
                )
            )
        );

        $representation = $user_access_key_representation_retriever->getByUserAndID($current_user, $id);

        if ($representation === null) {
            throw new RestException(404);
        }

        return $representation;
    }

    /**
     * Revoke an access key
     *
     * @url DELETE {id}
     *
     * @access protected
     *
     * @param int $id ID of the access key to delete
     *
     * @status 200
     */
    public function delete(int $id): void
    {
        $this->optionsId($id);
        $current_user = \UserManager::instance()->getCurrentUser();

        $revoker = new AccessKeyRevoker(new AccessKeyDAO());
        $revoker->revokeASetOfUserAccessKeys($current_user, [$id]);
    }
}
