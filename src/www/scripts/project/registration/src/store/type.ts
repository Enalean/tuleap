/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 */

import { FieldData, TemplateData, TroveCatData } from "../type";

export interface State {
    tuleap_templates: TemplateData[];
    selected_tuleap_template: TemplateData | null;
    selected_company_template: TemplateData | null;
    are_restricted_users_allowed: boolean;
    are_anonymous_allowed: boolean;
    project_default_visibility: string;
    error: string | null;
    is_creating_project: boolean;
    is_project_approval_required: boolean;
    trove_categories: TroveCatData[];
    is_description_required: boolean;
    project_fields: FieldData[];
    company_templates: TemplateData[];
    default_project_template: TemplateData | null;
    company_name: string;
    can_user_choose_project_visibility: boolean;
}

export interface Context {
    state: State;
    commit: Function;
}

export interface StoreOptions {
    state: {
        tuleap_templates: TemplateData[];
        selected_template: TemplateData | null;
        error: string | null;
        is_creating_project: boolean;
        project_default_visibility: string;
        is_project_approval_required: boolean;
        are_anonymous_allowed: boolean;
    };
}
