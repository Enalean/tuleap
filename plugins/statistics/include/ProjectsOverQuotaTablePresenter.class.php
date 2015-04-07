<?php
/**
 * Copyright (c) STMicroelectronics 2014. All rights reserved
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

class ProjectsOverQuotaTablePresenter {

    public $table_content;

    public function __construct($table_content) {
        $this->table_content  = $table_content;
    }

    public function getTemplateDir() {
        return ForgeConfig::get('codendi_dir') .'/plugins/statistics/templates';
    }
}
?>
