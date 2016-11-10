<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Trove;

use HTTPRequest;
use TroveCatDao;
use TroveCatFactory;

class TroveCatListController
{
    /**
     * @var TroveCatDao
     */
    private $trove_cat_dao;
    /**
     * @var TroveCatFactory
     */
    private $trove_cat_factory;

    public function __construct(TroveCatDao $trove_cat_dao, TroveCatFactory $trove_cat_factory)
    {
        $this->trove_cat_dao     = $trove_cat_dao;
        $this->trove_cat_factory = $trove_cat_factory;
    }

    public function update(HTTPRequest $request)
    {
        if (! $request->get('fullname')) {
            throw new TroveCatMissingFullNameException();
        }

        if (! $request->get('shortname')) {
            throw new TroveCatMissingShortNameException();
        }

        $shortname    = $request->get('shortname');
        $fullname     = $request->get('fullname');
        $description  = $request->get('description');
        $parent       = $request->get('parent');
        $display      = $this->isANewRootChild($request->get('parent'), $request->get('display-at-project-creation'));
        $mandatory    = $this->isMandatory($display, $request->get('is-mandatory'));
        $trove_cat_id = $request->get('id');

        $this->trove_cat_dao->updateTroveCat(
            $shortname,
            $fullname,
            $description,
            $parent,
            $display,
            $mandatory,
            $display,
            $trove_cat_id
        );
    }

    private function isMandatory($newroot, $mandatory)
    {
        if ($newroot !== 0) {
            $mandatory = 0;
        }

        return $mandatory;
    }

    private function isANewRootChild($id, $display)
    {
        $list_of_top_level_category_ids = array_keys($this->trove_cat_factory->getMandatoryParentCategoriesUnderRoot());

        if (! in_array($id, $list_of_top_level_category_ids)) {
            $display = 0;
        }

        return $display;
    }
}
