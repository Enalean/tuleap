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

import { createListPicker } from "@tuleap/list-picker";
import type { GetText } from "@tuleap/core/scripts/tuleap/gettext/vue-gettext-init";

export async function displayTeamsToAggregate(
    gettext_provider: GetText,
    doc: Document
): Promise<void> {
    const list_teams = doc.getElementById("program-management-choose-teams");
    if (!list_teams || !(list_teams instanceof HTMLSelectElement)) {
        throw new Error("No list to pick teams");
    }

    await createListPicker(list_teams, {
        locale: doc.body.dataset.userLocale,
        placeholder: gettext_provider.gettext("Choose a project to aggregate"),
        is_filterable: true,
    });
}
