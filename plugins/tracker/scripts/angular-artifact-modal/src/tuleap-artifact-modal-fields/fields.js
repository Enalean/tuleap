/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import angular from "angular";

import computed_field from "./computed-field/computed-field.js";
import permission_field from "./permission-field/permission-field.js";
import file_field from "./file-field/file-field.js";
import date_field from "./date-field/date-field.js";
import open_list_field from "./open-list-field/open-list-field.js";
import link_field from "./link-field/link-field.js";

export default angular.module("tuleap-artifact-modal-fields", [
    file_field,
    computed_field,
    permission_field,
    date_field,
    open_list_field,
    link_field,
]).name;
