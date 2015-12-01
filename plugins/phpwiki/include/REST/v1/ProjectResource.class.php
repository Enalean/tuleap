<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

use PaginatedPHPWikiPagesFactory;
use PFUser;

class ProjectResource {

    /** @var PaginatedPHPWikiPagesFactory */
    private $wiki_pages_factory;

    public function __construct(PaginatedPHPWikiPagesFactory $wiki_pages_factory) {
        $this->wiki_pages_factory = $wiki_pages_factory;
    }

    /**
     * @param PFUser $user
     * @param $project_id
     * @param $limit
     * @param $offset
     * @param $pagename
     * @return array {@type Tuleap\REST\v1\PhpWikiPageRepresentation}
     */
    public function getPhpWikiPlugin(PFUser $user, $project_id, $limit, $offset, $pagename) {
        $all_pages = $this->wiki_pages_factory->getPaginatedUserPages(
            $user,
            $project_id,
            $limit,
            $offset,
            $pagename
        );

        $pages = array();

        foreach ($all_pages->getPages() as $page) {
            $representation = new PhpWikiPluginPageRepresentation();
            $representation->build($page);

            $pages[] = $representation;
        }

        return $pages;
    }

}