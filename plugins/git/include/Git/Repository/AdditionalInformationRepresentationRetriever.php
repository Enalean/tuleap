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

namespace Tuleap\Git\Repository;

use Tuleap\Event\Dispatchable;

class AdditionalInformationRepresentationRetriever implements Dispatchable
{
    const NAME = 'additionalInformationRepresentationRetriever';
    /**
     * @var \GitRepository
     */
    private $repository;

    /**
     * @var array
     */
    private $additional_information = [];

    public function __construct(\GitRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return \GitRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    public function addInformation($key, $information)
    {
        $this->additional_information[$key] = $information;
    }

    public function getAdditionalInformation()
    {
        return $this->additional_information;
    }
}
