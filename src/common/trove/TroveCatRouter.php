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

use CSRFSynchronizerToken;
use Feedback;
use ForgeConfig;
use HTTPRequest;
use Tuleap\Admin\AdminPageRenderer;

class TroveCatRouter
{
    /**
     * @var TroveCatHierarchyRetriever
     */
    private $list_builder;
    /**
     * @var AdminPageRenderer
     */
    private $admin_renderer;
    /**
     * @var TroveCatListController
     */
    private $trove_cat_list_controller;

    public function __construct(
        TroveCatHierarchyRetriever $list_builder,
        AdminPageRenderer $admin_renderer,
        TroveCatListController $trove_cat_list_controller
    ) {
        $this->list_builder              = $list_builder;
        $this->admin_renderer            = $admin_renderer;
        $this->trove_cat_list_controller = $trove_cat_list_controller;
    }

    public function route(HTTPRequest $request)
    {
        $action = $request->get('action');

        try {
            switch ($action) {
                case "update":
                    $token = $this->getCSRF();
                    $token->check();
                    $this->trove_cat_list_controller->update($request);
                    $GLOBALS['Response']->redirect('/admin/trove/trove_cat_list.php');
                    break;
                case "add":
                    $token = $this->getCSRF();
                    $token->check();
                    $this->trove_cat_list_controller->add($request);
                    $GLOBALS['Response']->redirect('/admin/trove/trove_cat_list.php');
                    break;
                case "delete":
                    $token = $this->getCSRF();
                    $token->check();
                    $this->trove_cat_list_controller->delete($request);
                    $GLOBALS['Response']->redirect('/admin/trove/trove_cat_list.php');
                    break;
                default:
                    $this->displayList();
                    break;
            }
        } catch (TroveCatMissingFullNameException $e) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('admin_trove_cat_edit', 'missing_fullname')
            );
            $GLOBALS['Response']->redirect('/admin/trove/trove_cat_list.php');
        } catch (TroveCatMissingShortNameException $e) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('admin_trove_cat_edit', 'missing_shortname')
            );
            $GLOBALS['Response']->redirect('/admin/trove/trove_cat_list.php');
        } catch (CannotMoveFatherInChildException $e) {
            $GLOBALS['Response']->addFeedback(
                'error',
                $GLOBALS['Language']->getText('admin_trove_cat_edit', 'update_forbidden')
            );
            $GLOBALS['Response']->redirect('/admin/trove/trove_cat_list.php');
        } catch (TroveCatParentIsRootException $e) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('admin_trove_cat_delete', 'parent_is_root')
            );
            $GLOBALS['Response']->redirect('/admin/trove/trove_cat_list.php');
        }
    }

    private function displayList()
    {
        $last_parent    = array();
        $already_seen   = array();
        $trove_cat_list = array();
        $hierarchy_ids  = array();

        $this->list_builder->retrieveFullHierarchy(0, $last_parent, $already_seen, $trove_cat_list, $hierarchy_ids);

        $presenter  = new TroveCatListPresenter($trove_cat_list, $this->getCSRF());

        $this->admin_renderer->renderAPresenter(
            $GLOBALS['Language']->getText('admin_trove_cat_list', 'title'),
            ForgeConfig::get('codendi_dir') . '/src/templates/admin/trovecategories',
            'trovecatlist',
            $presenter
        );
    }

    private function getCSRF()
    {
        return new CSRFSynchronizerToken('/admin/trove/trove_cat_list.php');
    }
}
