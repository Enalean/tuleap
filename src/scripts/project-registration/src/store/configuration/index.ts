/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
import type { FieldData, TemplateData, TroveCatData, ExternalTemplateData } from "../../type";
import type { Module } from "vuex";
import type { RootState } from "../type";

export interface ConfigurationState {
    readonly are_restricted_users_allowed: boolean;
    readonly project_default_visibility: string;
    readonly is_project_approval_required: boolean;
    readonly trove_categories: TroveCatData[];
    readonly is_description_required: boolean;
    readonly project_fields: Array<FieldData>;
    readonly company_templates: TemplateData[];
    readonly tuleap_templates: TemplateData[];
    readonly external_templates: ExternalTemplateData[];
    readonly company_name: string;
    readonly can_user_choose_project_visibility: boolean;
}

export function createConfigurationModule(
    initial_configuration_state: ConfigurationState,
): Module<ConfigurationState, RootState> {
    return {
        namespaced: true,
        state: initial_configuration_state,
    };
}
