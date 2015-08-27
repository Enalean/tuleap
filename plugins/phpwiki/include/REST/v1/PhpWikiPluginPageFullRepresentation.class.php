<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

use Tuleap\PhpWiki\REST\v1\PhpWikiPluginPageVersionRepresentation;
use PHPWikiPage;
use PHPWikiVersionDao;
use PHPWikiPageVersionFactory;

class PhpWikiPluginPageFullRepresentation extends PhpWikiPluginPageRepresentation {

    /**
     * @var int {@type int}
     */
    public $last_version;

    /**
     * @var array {@type Tuleap\PhpWiki\REST\v1\PhpWikiPluginPageVersionRepresentation}
     */
    public $versions;

    public function build(PHPWikiPage $page) {
        parent::build($page);

        $this->last_version  = (int) $page->getCurrentVersion();
        $this->versions      = $this->getVersionsRepresentations();
    }

    private function getVersionsRepresentations() {
        $representations = array();

        $wiki_version_factory = new PHPWikiPageVersionFactory(new PHPWikiVersionDao());

        $page_versions = $wiki_version_factory->getPageAllVersions($this->id);

        foreach ($page_versions as $page_version) {
            $page_version_representation = new PhpWikiPluginPageVersionRepresentation();
            $page_version_representation->build($page_version);

            $representations[] = $page_version_representation;
        }

        return $representations;
    }
}