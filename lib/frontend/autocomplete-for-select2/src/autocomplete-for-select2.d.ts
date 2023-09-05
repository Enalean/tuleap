/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import type { Select2Plugin } from "tlp";

interface OptionsProjectSelect2 {
    include_private_projects?: boolean;
    placeholder?: string;
    minimumInputLength?: number;
}

interface OptionsUserSelect2 {
    use_tuleap_id?: boolean;
    internal_users_only?: 0 | 1;
    placeholder?: string;
    project_id?: string | undefined;
}

export function autocomplete_projects_for_select2(
    element: Element,
    options: OptionsProjectSelect2,
): void;

export function autocomplete_users_for_select2(
    element: Element,
    options: OptionsUserSelect2,
): Select2Plugin;
