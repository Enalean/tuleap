<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\User\AccessKey\AccessKeyCreationNotifier;
use Tuleap\User\AccessKey\AccessKeyCreator;
use Tuleap\User\AccessKey\AccessKeyDAO;
use Tuleap\User\AccessKey\AccessKeyRevoker;
use Tuleap\User\AccessKey\AccessKeySerializer;
use Tuleap\User\AccessKey\LastAccessKeyIdentifierStore;

class AccessKeyResource extends AuthenticatedResource
{
    const ROUTE = 'access_keys';

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
     * @url POST
     *
     * @access protected
     *
     * @param string $description Description of the use for which the access key is intended
     *
     * @status 201
     *
     * @return UserAccessKeyCreationRepresentation
     */
    public function post($description = '')
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

        $access_key_creator->create($current_user, $description);

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
