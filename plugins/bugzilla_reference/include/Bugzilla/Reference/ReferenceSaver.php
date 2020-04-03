<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Bugzilla\Reference;

use ReferenceManager;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Symmetric\EncryptionKey;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;
use Tuleap\reference\ReferenceValidator;
use Valid_HTTPURI;

class ReferenceSaver
{
    /**
     * @var Dao
     */
    private $dao;

    /**
     * @var ReferenceValidator
     */
    private $reference_validator;
    /**
     * @var ReferenceRetriever
     */
    private $reference_retriever;
    /**
     * @var ReferenceManager
     */
    private $reference_manager;
    /**
     * @var EncryptionKey
     */
    private $encryption_key;

    public function __construct(
        Dao $dao,
        ReferenceValidator $reference_validator,
        ReferenceRetriever $reference_retriever,
        ReferenceManager $reference_manager,
        EncryptionKey $encryption_key
    ) {
        $this->dao                 = $dao;
        $this->reference_validator = $reference_validator;
        $this->reference_retriever = $reference_retriever;
        $this->reference_manager   = $reference_manager;
        $this->encryption_key      = $encryption_key;
    }

    public function save(\Codendi_Request $request)
    {
        $keyword               = $request->get('keyword');
        $server                = trim($request->get('server'));
        $username              = $request->get('username');
        $api_key               = $request->get('api_key');
        $are_followups_private = $request->get('are_follow_up_private');
        $rest_api_url          = $request->get('rest_url');
        $are_followups_private = isset($are_followups_private) ? $are_followups_private : false;

        if (empty($keyword) || empty($server) || empty($username) || empty($api_key)) {
            throw new RequiredFieldEmptyException();
        }

        $this->checkFieldsValidity($keyword, $server, $rest_api_url);
        $this->createReferenceForBugzillaServer($keyword, $server);

        $encrypted_api_key = SymmetricCrypto::encrypt(new ConcealedString($api_key), $this->encryption_key);

        $this->dao->save($keyword, $server, $username, $encrypted_api_key, $are_followups_private, $rest_api_url);
    }

    private function checkFieldsValidity($keyword, $server, $rest_api_url)
    {
        if (! $this->reference_validator->isValidKeyword($keyword)) {
            throw new KeywordIsInvalidException();
        }

        if (
            $this->reference_validator->isSystemKeyword($keyword)
            || $this->reference_validator->isReservedKeyword($keyword)
            || $this->reference_retriever->getReferenceByKeyword($keyword) !== null
        ) {
            throw new KeywordIsAlreadyUsedException();
        }

        $this->validateURLs($server, $rest_api_url);
    }

    private function validateURLs($server, $rest_api_url)
    {
        $http_uri_validator = new Valid_HTTPURI();
        if (! $http_uri_validator->validate($server)) {
            throw new ServerIsInvalidException();
        }

        if ($rest_api_url && ! $http_uri_validator->validate($rest_api_url)) {
            throw new RESTURLIsInvalidException();
        }
    }

    public function edit(\Codendi_Request $request)
    {
        $id                    = $request->get('id');
        $server                = trim($request->get('server'));
        $rest_api_url          = trim($request->get('rest_url'));
        $username              = $request->get('username');
        $are_followups_private = $request->get('are_follow_up_private');
        $are_followups_private = isset($are_followups_private) ? $are_followups_private : false;

        $this->validateURLs($server, $rest_api_url);

        if (empty($server) || empty($username)) {
            throw new RequiredFieldEmptyException();
        }

        list($encrypted_api_key, $has_api_key_always_been_encrypted) = $this->getAPIKeyToStoreWithEncryptionStatus(
            $id,
            $request->get('api_key')
        );

        $this->dao->edit(
            $id,
            $server,
            $username,
            $encrypted_api_key,
            $has_api_key_always_been_encrypted,
            $are_followups_private,
            $rest_api_url
        );
    }

    private function getAPIKeyToStoreWithEncryptionStatus($id, $api_key)
    {
        if ($api_key !== '') {
            return array(SymmetricCrypto::encrypt(new ConcealedString($api_key), $this->encryption_key), true);
        }

        $reference = $this->dao->getReferenceById($id);
        if ($reference['api_key'] !== '') {
            return array(SymmetricCrypto::encrypt(new ConcealedString($reference['api_key']), $this->encryption_key), false);
        }

        return array($reference['encrypted_api_key'], $reference['has_api_key_always_been_encrypted']);
    }

    private function createReferenceForBugzillaServer($keyword, $server)
    {
        $reference = new \Reference(
            0,
            $keyword,
            'Bugzilla reference',
            $server . '/show_bug.cgi?id=$1',
            'S',
            '',
            'bugzilla',
            '1',
            100
        );

        if (! $this->reference_manager->createSystemReference($reference)) {
            throw new UnableToCreateSystemReferenceException();
        }
    }
}
