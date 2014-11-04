<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class ElasticSearch_SearchResultOwnerFacet {

    const IDENTIFIER = 'owner';

    public $value;

    public $checked = false;

    public function __construct(array $submitted_facets, PFUser $current_user) {
        $this->value   = $current_user->getId();
        $this->checked = isset($submitted_facets[self::IDENTIFIER]);

    }

    public function identifier() {
        return self::IDENTIFIER;
    }

    public function label() {
        return $GLOBALS['Language']->getText('plugin_fulltextsearch', 'facet_owner_label');
    }

}
