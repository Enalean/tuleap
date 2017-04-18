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

use Tuleap\reference\ReferenceValidator;
use Valid_HTTPSURI;

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

    public function __construct(
        Dao $dao,
        ReferenceValidator $reference_validator,
        ReferenceRetriever $reference_retriever
    ) {
        $this->dao                 = $dao;
        $this->reference_validator = $reference_validator;
        $this->reference_retriever = $reference_retriever;
    }

    public function save(\Codendi_Request $request)
    {
        $keyword               = $request->get('keyword');
        $server                = $request->get('server');
        $username              = $request->get('username');
        $password              = $request->get('password');
        $are_followups_private = $request->get('are_follow_up_private');
        $are_followups_private = isset($are_followups_private) ? $are_followups_private : false;

        if (! $this->areFieldsSet($keyword, $server, $username, $password)) {
            throw new RequiredFieldEmptyException();
        }

        $this->checkFieldsValidity($keyword, $server);

        $this->dao->save($keyword, $server, $username, $password, $are_followups_private);
    }

    private function areFieldsSet($keyword, $server, $username, $password)
    {
        return isset($keyword)
            && isset($server)
            && isset($username)
            && isset($password);
    }

    private function checkFieldsValidity($keyword, $server)
    {
        if (! $this->reference_validator->isValidKeyword($keyword)) {
            throw new KeywordIsInvalidException();
        }

        if ($this->reference_validator->isSystemKeyword($keyword)
            || $this->reference_validator->isReservedKeyword($keyword)
            || $this->reference_retriever->getReferenceByKeyword($keyword) !== null
        ) {
            throw new KeywordIsAlreadyUsedException();
        }

        $this->checkServerValidity($server);
    }

    private function checkServerValidity($server)
    {
        $http_uri_validator = new Valid_HTTPSURI();

        if (! $http_uri_validator->validate($server)) {
            throw new ServerIsInvalidException();
        }
    }

    public function edit($request)
    {
        $id                    = $request->get('id');
        $keyword               = $request->get('keyword');
        $server                = $request->get('server');
        $username              = $request->get('username');
        $password              = $this->getPassowrdToStore($id, $request->get('password'));
        $are_followups_private = $request->get('are_follow_up_private');
        $are_followups_private = isset($are_followups_private) ? $are_followups_private : false;

        $this->checkServerValidity($server);

        if (! $this->areFieldsSet($keyword, $server, $username, $password)) {
            throw new RequiredFieldEmptyException();
        }

        $this->dao->edit($id, $server, $username, $password, $are_followups_private);
    }

    private function getPassowrdToStore($id, $password)
    {
        if ($password !== "") {
            return $password;
        }

        $reference = $this->dao->getReferenceById($id);
        return $reference['password'];
    }
}
