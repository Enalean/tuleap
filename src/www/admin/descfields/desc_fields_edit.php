<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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
 *
 * Originally written by Benjamin Ninassi 2008, Codendi Team, Xerox
 */

require_once 'pre.php';

use Tuleap\Project\DescriptionFieldsDao;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Project\Admin\DescriptionFields\FieldsListPresenter;
use Tuleap\Project\Admin\DescriptionFields\DescriptionFieldAdminPresenterBuilder;

$request = HTTPRequest::instance();
$request->checkUserIsSuperUser();

$is_an_update_request = $request->isPost();
$csrf_token           = new CSRFSynchronizerToken('/admin/descfields/desc_fields_edit.php');
if ($is_an_update_request) {
    $csrf_token->check();
}

$GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/tuleap/admin-description-fields.js');

$delete_desc_id = $request->get('delete_group_desc_id');
if ($delete_desc_id && $is_an_update_request) {
    $sql    = "DELETE FROM group_desc where group_desc_id='" . db_ei($delete_desc_id) . "'";
    $result = db_query($sql);

    if (!$result) {
        list($host, $port) = explode(':', $GLOBALS['sys_default_domain']);
        $GLOBALS['Response']->addFeedback(Feedback::ERROR, $Language->getText('admin_desc_fields', 'del_desc_field_fail', array($host, db_error())));
    }

    $sql    = "DELETE FROM group_desc_value where group_desc_id='" . db_ei($delete_desc_id) . "'";
    $result = db_query($sql);

    if (!$result) {
        list($host, $port) = explode(':', $GLOBALS['sys_default_domain']);
        $GLOBALS['Response']->addFeedback(Feedback::ERROR, $Language->getText('admin_desc_fields', 'del_desc_field_fail', array($host, db_error())));
    } else {
        $GLOBALS['Response']->addFeedback(Feedback::INFO, $Language->getText('admin_desc_fields', 'remove_success'));
    }

    $GLOBALS['Response']->redirect('/admin/descfields/desc_fields_edit.php');
}

$make_required_desc_id   = $request->get('make_required_desc_id');
$remove_required_desc_id = $request->get('remove_required_desc_id');
if ($make_required_desc_id && $is_an_update_request) {
    $sql    = "UPDATE group_desc SET desc_required='1' where group_desc_id='" . db_ei($make_required_desc_id) . "'";
    $result = db_query($sql);
    if (!$result) {
        list($host, $port) = explode(':', $GLOBALS['sys_default_domain']);
        $GLOBALS['Response']->addFeedback(Feedback::ERROR, $Language->getText('admin_desc_fields', 'update_required_desc_field_fail', array($host, db_error())));
    } else {
        $GLOBALS['Response']->addFeedback(Feedback::INFO, $Language->getText('admin_desc_fields', 'update_success'));
    }

    $GLOBALS['Response']->redirect('/admin/descfields/desc_fields_edit.php');
}

if ($remove_required_desc_id && $is_an_update_request) {
    $sql    = "UPDATE group_desc SET desc_required='0' where group_desc_id='" . db_ei($remove_required_desc_id) . "'";
    $result = db_query($sql);
    if (!$result) {
        list($host, $port) = explode(':', $GLOBALS['sys_default_domain']);
        $GLOBALS['Response']->addFeedback(Feedback::ERROR, $Language->getText('admin_desc_fields', 'update_required_desc_field_fail', array($host, db_error())));
    } else {
        $GLOBALS['Response']->addFeedback(Feedback::INFO, $Language->getText('admin_desc_fields', 'update_success'));
    }

    $GLOBALS['Response']->redirect('/admin/descfields/desc_fields_edit.php');
}

$update           = $request->get('update_desc');
$add_desc         = $request->get('add_desc');
$desc_name        = $request->get('form_name');
$desc_description = $request->get('form_desc');
$desc_type        = $request->get('form_type');
$desc_rank        = $request->get('form_rank');
$desc_required    = $request->get('form_required');

if (($add_desc || $update) && $is_an_update_request) {
    //data validation
    $valid_data = 1;
    if (!trim($desc_name) || !trim($desc_description)) {
        $GLOBALS['Response']->addFeedback(Feedback::ERROR, $Language->getText('admin_desc_fields', 'info_missed'));
        $valid_data = 0;
    }

    if (!is_numeric($desc_rank)) {
        $GLOBALS['Response']->addFeedback(Feedback::ERROR, $Language->getText('admin_desc_fields', 'info_rank_noint'));
        $valid_data = 0;
    }

    if ($valid_data == 1) {
        if ($add_desc) {
            $sql = "INSERT INTO group_desc (desc_name, desc_description, desc_rank, desc_type, desc_required) ";
            $sql .= "VALUES ('" . db_escape_string($desc_name) . "','" . db_escape_string($desc_description) . "','";
            $sql .= db_escape_string($desc_rank) . "','" . db_es($desc_type) . "','" . db_ei($desc_required) . "')";
            $result = db_query($sql);

            if (!$result) {
                list($host, $port) = explode(':', $GLOBALS['sys_default_domain']);
                $GLOBALS['Response']->addFeedback(Feedback::ERROR, $Language->getText('admin_desc_fields', 'ins_desc_field_fail', array($host, db_error())));
            } else {
                $GLOBALS['Response']->addFeedback(Feedback::INFO, $Language->getText('admin_desc_fields', 'add_success'));
            }
        } else {
            $sql = "UPDATE group_desc SET ";
            $sql .= "desc_name='" . db_escape_string($desc_name) . "',";
            $sql .= "desc_description='" . db_escape_string($desc_description) . "',";
            $sql .= "desc_rank='" . db_escape_string($desc_rank) . "',";
            $sql .= "desc_type='" . db_escape_string($desc_type) . "',";
            $sql .= "desc_required='" . db_escape_string($desc_required) . "'";
            $sql .= " WHERE group_desc_id='" . db_ei($request->get('form_desc_id')) . "'";

            $result = db_query($sql);

            if (!$result || db_affected_rows($result) < 1) {
                $GLOBALS['Response']->addFeedback(Feedback::ERROR, $Language->getText('admin_desc_fields', 'update_desc_field_fail', (db_error() ? db_error() : ' ')));
            } else {
                $GLOBALS['Response']->addFeedback(Feedback::INFO, $Language->getText('admin_desc_fields', 'update_success'));
            }
        }
    }
    if (($valid_data == 1) || (($valid_data == 0) && ($update))) {
        $desc_name        = '';
        $desc_description = '';
        $desc_type        = '';
        $desc_rank        = '';
        $desc_required    = '';
    }

    $GLOBALS['Response']->redirect('/admin/descfields/desc_fields_edit.php');
}

$description_fields_dao   = new DescriptionFieldsDao();
$description_fields_infos = $description_fields_dao->searchAll();

$field_builder    = new DescriptionFieldAdminPresenterBuilder();
$field_presenters = $field_builder->build($description_fields_infos);

$title = $Language->getText('admin_desc_fields', 'title');

$custom_project_fields_list_presenter = new FieldsListPresenter(
    $title,
    $field_presenters,
    $csrf_token
);

$admin_page = new AdminPageRenderer();
$admin_page->renderAPresenter(
    $Language->getText('admin_desc_fields', 'title'),
    ForgeConfig::get('codendi_dir') . '/src/templates/admin/description_fields/',
    FieldsListPresenter::TEMPLATE,
    $custom_project_fields_list_presenter
);
