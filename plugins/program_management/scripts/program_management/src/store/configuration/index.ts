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

import type { State } from "../../type";
import type { Module } from "vuex";
import type { ProjectFlag } from "@tuleap/vue-breadcrumb-privacy";
import type { ProjectPrivacy } from "@tuleap/project-privacy-helper";

export interface ConfigurationState {
    readonly public_name: string;
    readonly short_name: string;
    readonly project_icon: string;
    readonly privacy: ProjectPrivacy;
    readonly flags: Array<ProjectFlag>;
    readonly program_id: number;
    readonly accessibility: boolean;
    readonly user_locale: string;
    readonly can_create_program_increment: boolean;
    readonly has_plan_permissions: boolean;
    readonly tracker_program_increment_id: number;
    readonly tracker_program_increment_label: string;
    readonly tracker_program_increment_sub_label: string;
    readonly is_program_admin: boolean;
    readonly is_configured: boolean;
    readonly is_iteration_tracker_defined: boolean;
    readonly tracker_iteration_label: string;
}

export function createConfigurationModule(
    initial_configuration_state: ConfigurationState,
): Module<ConfigurationState, State> {
    return {
        namespaced: true,
        state: initial_configuration_state,
    };
}
