/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { State } from "./type";
import { ACCESS_PRIVATE } from "../constant";

const state: State = {
    tuleap_templates: [],
    selected_template: null,
    are_restricted_users_allowed: false,
    project_default_visibility: ACCESS_PRIVATE,
    error: null,
    is_creating_project: false,
    is_project_approval_required: false,
    trove_categories: [],
    is_description_required: false,
    project_fields: []
};

export default state;
