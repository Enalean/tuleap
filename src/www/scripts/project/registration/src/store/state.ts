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

const state: State = {
    tuleap_templates: [],
    selected_tuleap_template: null,
    selected_company_template: null,
    are_restricted_users_allowed: false,
    are_anonymous_allowed: false,
    project_default_visibility: "",
    error: null,
    is_creating_project: false,
    is_project_approval_required: false,
    trove_categories: [],
    is_description_required: false,
    project_fields: [],
    company_templates: [],
    default_project_template: null,
    company_name: "",
    can_user_choose_project_visibility: false,
};

export default state;
