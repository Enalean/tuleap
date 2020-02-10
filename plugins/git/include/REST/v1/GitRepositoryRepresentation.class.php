<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Git\REST\v1;

use GitRepository;
use Tuleap\REST\JsonCast;
use Tuleap\REST\v1\GitRepositoryRepresentationBase;

class GitRepositoryRepresentation extends GitRepositoryRepresentationBase
{
    /**
     * @param string        $html_url
     * @param               $server_representation
     * @param string        $last_update_date
     * @param array         $additional_information
     */
    public function build(
        GitRepository $repository,
        $html_url,
        $server_representation,
        $last_update_date,
        array $additional_information
    ) {
        $this->id                     = JsonCast::toInt($repository->getId());
        $this->uri                    = self::ROUTE . '/' . $this->id;
        $this->name                   = $repository->getName();
        $this->label                  = $repository->getLabel();
        $this->path                   = $repository->getPath();
        $this->path_without_project   = $repository->getPathWithoutProject();
        $this->description            = $repository->getDescription();
        $this->server                 = $server_representation;
        $this->html_url               = $html_url;
        $this->last_update_date       = JsonCast::toDate($last_update_date);
        $this->additional_information = $additional_information;
    }
}
