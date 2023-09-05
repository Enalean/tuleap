/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import type { Module } from "vuex";
import type { ProjectFlag } from "@tuleap/vue-breadcrumb-privacy";
import type { State } from "../../type";
import type { ProjectPrivacy } from "@tuleap/project-privacy-helper";

export interface ConfigurationState {
    readonly program: Program;
    readonly program_privacy: ProjectPrivacy;
    readonly program_flags: Array<ProjectFlag>;
    readonly is_program_admin: boolean;
    readonly program_increment: ProgramIncrement;
    readonly iterations_labels: IterationLabels;
    readonly user_locale: string;
    readonly iteration_tracker_id: number;
    readonly is_accessibility_mode_enabled: boolean;
}

export interface Program {
    program_label: string;
    program_shortname: string;
    program_icon: string;
}

export interface ProgramIncrement {
    id: number;
    title: string;
    start_date: string;
    end_date: string;
}

export interface IterationLabels {
    label: string;
    sub_label: string;
}

export function createConfigurationModule(
    initial_configuration_state: ConfigurationState,
): Module<ConfigurationState, State> {
    return {
        namespaced: true,
        state: initial_configuration_state,
    };
}
