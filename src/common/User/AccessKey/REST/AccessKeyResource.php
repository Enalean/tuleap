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

namespace Tuleap\User\AccessKey\REST;

use DateTime;
use DateTimeImmutable;
use Luracast\Restler\RestException;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\User\AccessKey\AccessKeyAlreadyExpiredException;
use Tuleap\User\AccessKey\AccessKeyCreationNotifier;
use Tuleap\User\AccessKey\AccessKeyCreator;
use Tuleap\User\AccessKey\AccessKeyDAO;
use Tuleap\User\AccessKey\AccessKeyRevoker;
use Tuleap\User\AccessKey\AccessKeySerializer;
use Tuleap\User\AccessKey\LastAccessKeyIdentifierStore;

class AccessKeyResource extends AuthenticatedResource
{
    public const ROUTE = 'access_keys';

    /**
     * @url OPTIONS
     * @access protected
     */
    public function options()
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
     *   "expiration_date": "2019-08-07T10:15:57+02:00"<br/>
     * }<br/>
     * </pre>
     * <br/>
     * The expiration date is optional. If provided, it must be formatted as an ISO-8601 date.<br/>
     * In addition, you cannot create an already expired access key.
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
            new AccessKeySerializer(),
            (new KeyFactory)->getEncryptionKey(),
            $storage_access_key_identifier_store
        );
        $access_key_creator               = new AccessKeyCreator(
            $last_access_key_identifier_store,
            new AccessKeyDAO(),
            new SplitTokenVerificationStringHasher(),
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

        try {
            $access_key_creator->create($current_user, $access_key->description, $expiration_date);
        } catch (AccessKeyAlreadyExpiredException $exception) {
            throw new RestException(400, $exception->getMessage());
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
     */
    public function optionsId($id)
    {
        Header::allowOptionsDelete();
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
    public function delete($id)
    {
        $this->optionsId($id);
        $current_user = \UserManager::instance()->getCurrentUser();

        $revoker = new AccessKeyRevoker(new AccessKeyDAO());
        $revoker->revokeASetOfUserAccessKeys($current_user, [$id]);
    }
}
