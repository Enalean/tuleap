<?php
/**
 * Copyright Enalean (c) 2016 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\SVN\Admin;

use Tuleap\SVNCore\Repository;
use Tuleap\SVN\REST\v1\ImmutableTagRepresentation;

class ImmutableTagFactory
{
    /**
     * @var ImmutableTagDao
     */
    private $dao;

    public function __construct(ImmutableTagDao $dao)
    {
        $this->dao = $dao;
    }

    public function getByRepositoryId(Repository $repository): ImmutableTag
    {
        $row = $this->dao->searchByRepositoryId($repository->getId());
        if (! $row) {
            return $this->instantiateEmptyImmutableTag($repository);
        }

        return $this->instantiateFromRow($repository, $row);
    }

    public function getEmpty(Repository $repository): ImmutableTag
    {
        return $this->instantiateEmptyImmutableTag($repository);
    }

    private function instantiateEmptyImmutableTag(Repository $repository): ImmutableTag
    {
        return ImmutableTag::buildEmptyImmutableTag($repository);
    }

    private function instantiateFromRow(Repository $repository, array $row): ImmutableTag
    {
        return new ImmutableTag(
            $repository,
            $row['paths'],
            $row['whitelist']
        );
    }

    public function getFromRESTRepresentation(
        Repository $repository,
        ImmutableTagRepresentation $immutable_tag_representation,
    ): ImmutableTag {
        $row = [];

        $row['paths']     = implode(PHP_EOL, $immutable_tag_representation->paths);
        $row['whitelist'] = implode(PHP_EOL, $immutable_tag_representation->whitelist);

        return $this->instantiateFromRow($repository, $row);
    }
}
