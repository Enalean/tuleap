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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import { State } from "./type";
import { TemplateData } from "../type";
import { FetchWrapperError } from "tlp";

export default {
    setSelectedTemplate(state: State, selected_template: TemplateData): void {
        if (selected_template.is_built_in) {
            state.selected_tuleap_template = selected_template;
            state.selected_company_template = null;
        } else {
            state.selected_tuleap_template = null;
            state.selected_company_template = selected_template;
        }
    },

    resetSelectedTemplate(state: State): void {
        state.selected_tuleap_template = null;
        state.selected_company_template = null;
    },

    setIsCreatingProject(state: State, is_creating_project: boolean): void {
        state.error = null;
        state.is_creating_project = is_creating_project;
    },

    async handleError(state: State, rest_error: FetchWrapperError): Promise<void> {
        try {
            const { error } = await rest_error.response.json();
            state.error = error.message;
        } catch (e) {
            state.error = "Internal server error";
            throw e;
        }
    },

    resetError(state: State): void {
        state.error = null;
    },
};
