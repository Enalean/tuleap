<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

namespace Tuleap\PhpWiki\REST\v1;

use Tuleap\PHPWiki\WikiPage;
use Tuleap\REST\v1\PhpWikiPageRepresentation;
use WikiVersionDao;
use WikiPageVersionFactory;

/**
 * @psalm-immutable
 */
class PhpWikiPageFullRepresentation extends PhpWikiPageRepresentation
{

    /**
     * @var int {@type int}
     */
    public $last_version;

    /**
     * @var array {@type Tuleap\PhpWiki\REST\v1\PhpWikiPageVersionRepresentation}
     */
    public $versions;

    /**
     * @param PhpWikiPageVersionRepresentation[] $versions
     */
    private function __construct(int $id, string $name, int $last_version, array $versions)
    {
        parent::__construct($id, $name);
        $this->last_version = $last_version;
        $this->versions     = $versions;
    }

    public static function buildFull(WikiPage $page): self
    {
        $id = (int) $page->getId();
        return new self($id, $page->getPagename(), (int) $page->getLastVersionId(), self::getVersionRepresentations($id));
    }

    /**
     * @return PhpWikiPageVersionRepresentation[]
     */
    private static function getVersionRepresentations(int $id): array
    {
        $representations = [];

        $wiki_version_dao     = new WikiVersionDao();
        $wiki_version_factory = new WikiPageVersionFactory();

        foreach ($wiki_version_dao->getAllVersionForGivenPage($id) as $version) {
            $page_version = $wiki_version_factory->getInstanceFromRow($version);

            $page_version_representation = PhpWikiPageVersionRepresentation::build($page_version);

            $representations[] = $page_version_representation;
        }

        return $representations;
    }
}
