<?php
/**
 * SourceForge: Breaking Down the Barriers to Open Source Development
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

use Tuealp\trove\TroveCatListBuilder;
use Tuealp\trove\TroveCatListPresenter;
use Tuleap\Admin\AdminPageRenderer;

require_once('pre.php');

session_require(array('group' => '1', 'admin_flags' => 'A'));

$root_node         = array();
$last_parent[0]    = true;
$trove_cat_builder = new TroveCatListBuilder(new TroveCatDao());
$trove_cat_builder->build(0, $root_node, $last_parent);

$presenter = new TroveCatListPresenter($root_node);
$renderer  = new AdminPageRenderer();
$renderer->renderAPresenter(
    $GLOBALS['Language']->getText('admin_trove_cat_list', 'title'),
    ForgeConfig::get('codendi_dir') . '/src/templates/admin/trovecategories',
    'trovecatlist',
    $presenter
);
