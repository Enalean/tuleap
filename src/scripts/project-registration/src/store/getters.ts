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

import type { RootState } from "./type";
import type { AdvancedOptions, TemplateData } from "../type";

export const is_template_selected = (state: RootState): boolean =>
    state.selected_tuleap_template !== null || state.selected_company_template !== null;

export const is_currently_selected_template =
    (state: RootState) =>
    (template: TemplateData): boolean => {
        if (state.selected_company_template !== null) {
            return state.selected_company_template.id === template.id;
        }

        if (state.selected_tuleap_template !== null) {
            return state.selected_tuleap_template.id === template.id;
        }

        return false;
    };

export const has_error = (state: RootState): boolean => state.error !== null;

export const is_advanced_option_selected =
    (state: RootState) =>
    (option: AdvancedOptions | null): boolean => {
        return state.selected_advanced_option === option;
    };
