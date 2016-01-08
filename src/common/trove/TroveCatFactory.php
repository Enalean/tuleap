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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class TroveCatFactory {

    /**
     * @var TroveCatDao
     */
    private $dao;

    public function __construct(TroveCatDao $dao) {
        $this->dao = $dao;
    }

    /**
     * @return TroveCat[]
     */
    public function getMandatoryParentCategoriesUnderRoot() {
        $results    = $this->dao->getMandatoryParentCategoriesUnderRoot();
        $trove_cats = array();

        foreach ($results as $row) {
            $trove_cat = $this->getTroveCatWithChildrens($row);
            $trove_cats[$trove_cat->getId()] = $trove_cat;
        }

        return $trove_cats;
    }

    /**
     * @return TroveCat[]
     */
    public function getMandatoryParentCategoriesUnderRootOnlyWhenCategoryHasChildren() {
        $results    = $this->dao->getMandatoryParentCategoriesUnderRoot();
        $trove_cats = array();

        foreach ($results as $row) {
            $trove_cat = $this->getTroveCatWithChildrens($row);
            if (count($trove_cat->getChildren()) > 0) {
                $trove_cats[$trove_cat->getId()] = $trove_cat;
            }
        }

        return $trove_cats;
    }

    /**
     * @return TroveCat
     */
    private function getTroveCatWithChildrens(array $row) {
        $trove_cat_id = $row['trove_cat_id'];
        $trove_cat    = $this->getInstanceFromRow($row);

        foreach($this->dao->getCategoryChildrenToDisplayDuringProjectCreation($trove_cat_id) as $row_child) {
            $child = $this->getInstanceFromRow($row_child);
            $trove_cat->addChildren($child);
        }
        return $trove_cat;
    }

    /**
     * @return TroveCat
     */
    private function getInstanceFromRow(array $row) {
        return new TroveCat(
           $row['trove_cat_id'],
           $row['shortname'],
           $row['fullname']
        );
    }
}