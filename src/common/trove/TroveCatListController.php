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
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class TroveCatListController implements DispatchableWithRequest
{
    private const DEFAULT_NB_MAX_VALUES = 3;

    /**
     * @var TroveCatDao
     */
    private $trove_cat_dao;
    /**
     * @var TroveCatFactory
     */
    private $trove_cat_factory;
    /**
     * @var TroveCatHierarchyRetriever
     */
    private $trove_cat_retriever;

    public function __construct()
    {
        $this->trove_cat_dao       = new TroveCatDao();
        $this->trove_cat_factory   = new TroveCatFactory($this->trove_cat_dao);
        $this->trove_cat_retriever = new TroveCatHierarchyRetriever($this->trove_cat_dao);
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array $variables
     * @throws NotFoundException
     * @throws ForbiddenException
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $request->getCurrentUser()->isSuperUser()) {
            throw new ForbiddenException();
        }

        $csrf_token = new \CSRFSynchronizerToken('/admin/project-creation/categories');
        $csrf_token->check();

        switch ($request->get('action')) {
            case "update":
                $this->update($request);
                break;
            case "add":
                $this->add($request);
                break;
            case "delete":
                $this->delete($request);
                break;
        }
        $layout->redirect('/admin/project-creation/categories');
    }

    private function add(HTTPRequest $request)
    {
        $trove_category = $this->formatTroveCategoriesFromRequest($request);
        $this->trove_cat_dao->add(
            $trove_category['shortname'],
            $trove_category['fullname'],
            $trove_category['description'],
            $trove_category['parent'],
            $trove_category['root_parent'],
            $trove_category['mandatory'],
            $trove_category['display'],
            $trove_category['fullpath'],
            $trove_category['fullpath_ids'],
            $trove_category['nb_max_values'],
            $trove_category['is_project_flag']
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

    private function update(HTTPRequest $request)
    {
        $current_trove_category = $this->formatTroveCategoriesFromRequest($request);

        $last_parent             = array();
        $already_seen            = array();
        $trove_category_children = array();
        $last_parent_ids         = array();
        $this->trove_cat_retriever->retrieveChildren(
            $current_trove_category['trove_cat_id'],
            $last_parent,
            $already_seen,
            $trove_category_children,
            $last_parent_ids
        );

        $this->trove_cat_dao->startTransaction();

        if ($current_trove_category['parent'] ===  $current_trove_category['trove_cat_id']) {
            $this->trove_cat_dao->rollBack();
            throw new CannotMoveFatherInChildException();
        }

        $this->trove_cat_dao->updateTroveCat(
            $current_trove_category['shortname'],
            $current_trove_category['fullname'],
            $current_trove_category['description'],
            $current_trove_category['parent'],
            $current_trove_category['root_parent'],
            $current_trove_category['mandatory'],
            $current_trove_category['display'],
            $current_trove_category['trove_cat_id'],
            $current_trove_category['fullpath'],
            $current_trove_category['fullpath_ids'],
            $current_trove_category['nb_max_values'],
            $current_trove_category['is_project_flag']
        );

        $newroot_for_children = $current_trove_category['root_parent'];
        if (! $newroot_for_children) {
            $newroot_for_children = $current_trove_category['trove_cat_id'];
        }
        foreach ($trove_category_children as $child) {
            if ($current_trove_category['parent'] === $child['trove_cat_id']) {
                $this->trove_cat_dao->rollBack();
                throw new CannotMoveFatherInChildException();
            }
            $this->trove_cat_dao->updateTroveCat(
                $child['shortname'],
                $child['fullname'],
                $child['description'],
                $child['parent'],
                $newroot_for_children,
                $child['is_top_level_id'],
                $child['display_during_project_creation'],
                $child['trove_cat_id'],
                $current_trove_category['fullpath'] . ' :: ' . $child['hierarchy'],
                $current_trove_category['fullpath_ids'] . ' :: ' . $child['trove_cat_id'],
                $child['nb_max_values'],
                $child['is_project_flag']
            );
        }

        $this->trove_cat_dao->commit();
    }

    /**
     * @throws TroveCatMissingFullNameException
     * @throws TroveCatMissingShortNameException
     */
    private function formatTroveCategoriesFromRequest(HTTPRequest $request)
    {
        $trove_cat_id = '';
        $id_validator = new \Valid_Int('id');
        if ($request->valid($id_validator)) {
            $trove_cat_id = $request->get('id');
        }

        if (! $request->get('fullname')) {
            throw new TroveCatMissingFullNameException();
        }

        if (! $request->get('shortname')) {
            throw new TroveCatMissingShortNameException();
        }

        $nb_max_values = (int) $request->get('nb-max-values');
        if ($nb_max_values < 1) {
            $nb_max_values = self::DEFAULT_NB_MAX_VALUES;
        }

        $is_project_flag = $this->isProjectFlag($request, $nb_max_values);

        $display = $this->isANewRootChild(
            $request->get('parent'),
            $request->get('display-at-project-creation')
        );

        $trove_cat_list  = array();
        $already_seen    = array();
        $last_parent     = array();
        $last_parent_ids = array();
        $this->trove_cat_retriever->retrieveFathers(
            $request->get('parent'),
            $last_parent,
            $already_seen,
            $trove_cat_list,
            $last_parent_ids
        );

        $ids = array(0);
        if (isset($trove_cat_list['hierarchy_id'])) {
            $ids = explode(' :: ', $trove_cat_list['hierarchy_id']);
        }

        $trove_categories = array(
            'shortname'    => $request->get('shortname'),
            'fullname'     => $request->get('fullname'),
            'description'  => $request->get('description'),
            'parent'       => $request->get('parent'),
            'display'      => $display,
            'mandatory'    => $this->isMandatory($display, $request->get('is-mandatory')),
            'trove_cat_id' => $request->get('id'),
            'fullpath'     => (isset($trove_cat_list['hierarchy'])) ? $trove_cat_list['hierarchy'] .  " :: " . $request->get('fullname') : $request->get('fullname'),
            'fullpath_ids' => (isset($trove_cat_list['hierarchy_id'])) ? $trove_cat_list['hierarchy_id'] . " :: "  . $trove_cat_id : $trove_cat_id,
            'root_parent'  => (int) $ids[0],
            'nb_max_values' => $nb_max_values,
            'is_project_flag' => $is_project_flag
        );

        return $trove_categories;
    }

    private function delete(HTTPRequest $request)
    {
        $trove_cat_id            = $request->get('trove_cat_id');
        $last_parent             = array();
        $already_seen            = array();
        $trove_category_children = array();
        $hierarchy_ids           = array();

        $this->trove_cat_retriever->retrieveChildren(
            $trove_cat_id,
            $last_parent,
            $already_seen,
            $trove_category_children,
            $hierarchy_ids
        );

        $this->trove_cat_dao->startTransaction();

        foreach ($trove_category_children as $child) {
            $this->trove_cat_dao->delete($child['trove_cat_id']);
        }

        $this->trove_cat_dao->delete($request->get('trove_cat_id'));

        $this->trove_cat_dao->commit();
    }

    /**
     *
     * @return bool
     * @throws \Exception
     */
    private function isProjectFlag(HTTPRequest $request, $nb_max_values)
    {
        $is_project_flag = $request->get('is-project-flag') === "1";
        if ($is_project_flag) {
            if ($request->get('parent') !== "0") {
                throw new \Exception("Only top categories can be marked as project flag.");
            }

            $this->checkNbOfExistingProjectFlags($request);

            if ($nb_max_values !== 1) {
                throw new \Exception("Only categories with nb max values = 1 can be marked as project flag.");
            }
        }

        return $is_project_flag;
    }

    private function checkNbOfExistingProjectFlags(HTTPRequest $request)
    {
        $last_parent    = [];
        $already_seen   = [];
        $trove_cat_list = [];
        $hierarchy_ids  = [];

        $this->trove_cat_retriever->retrieveFullHierarchy(
            0,
            $last_parent,
            $already_seen,
            $trove_cat_list,
            $hierarchy_ids
        );

        $id                                             = $request->get('id');
        $nb_of_other_categories_flagged_as_project_flag = 0;
        foreach ($trove_cat_list as $trovecat) {
            if ($trovecat['trove_cat_id'] === $id) {
                continue;
            }
            if ($trovecat['is_top_level_id'] && $trovecat['is_project_flag']) {
                $nb_of_other_categories_flagged_as_project_flag++;
            }
        }
        if ($nb_of_other_categories_flagged_as_project_flag >= 2) {
            throw new \Exception("Up to 2 categories can be used as flag.");
        }
    }
}
