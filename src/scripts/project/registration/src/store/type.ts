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

import type { TemplateData } from "../type";
import type { ConfigurationState } from "./configuration";

export interface State {
    selected_tuleap_template: TemplateData | null;
    selected_company_template: TemplateData | null;
    error: string | null;
    is_creating_project: boolean;
}

export interface StoreOptions {
    state: {
        tuleap_templates: TemplateData[];
        selected_template: TemplateData | null;
        error: string | null;
        is_creating_project: boolean;
        project_default_visibility: string;
        is_project_approval_required: boolean;
    };
}

export interface RootState extends State {
    readonly configuration: ConfigurationState;
}
