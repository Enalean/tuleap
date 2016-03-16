<?php
/**
 * Copyright Enalean (c) 2016. All rights reserved.
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

namespace Tuleap\Svn\Admin;

use Tuleap\Svn\Repository\Repository;

class ImmutableTagCreator {

    private $dao;

    public function __construct(ImmutableTagDao $dao) {
        $this->dao = $dao;
    }

    public function save(Repository $repository, $immutable_tags_path) {
        if (! $this->dao->save($repository, $this->cleanImmutableTag($immutable_tags_path))) {
            throw new CannotCreateImmuableTagException ($GLOBALS['Language']->getText('plugin_svn','create_immutable_tag_error'));
        }
    }

    private function cleanImmutableTag($immutable_tags_path) {
        $immutable_paths = explode(PHP_EOL, $immutable_tags_path);
        return implode(PHP_EOL, array_map('trim',$immutable_paths));
    }

}
