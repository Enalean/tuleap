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
import type { GetText } from "@tuleap/core/scripts/tuleap/gettext/gettext-init";

export async function initListPickersMilestoneSection(
    doc: Document,
    gettext_provider: GetText
): Promise<void> {
    const program_increment_tracker_element = getHTMLSelectElementFromId(
        doc,
        "admin-configuration-program-increment-tracker"
    );

    const plannable_trackers_element = getHTMLSelectElementFromId(
        doc,
        "admin-configuration-plannable-trackers"
    );

    const permission_prioritize_element = getHTMLSelectElementFromId(
        doc,
        "admin-configuration-permission-prioritize"
    );

    await createListPicker(program_increment_tracker_element, {
        locale: doc.body.dataset.userLocale,
        placeholder: gettext_provider.gettext("Choose a source tracker for Program Increments"),
        is_filterable: true,
    });

    await createListPicker(plannable_trackers_element, {
        locale: doc.body.dataset.userLocale,
        placeholder: gettext_provider.gettext("Choose which trackers can be planned"),
        is_filterable: true,
    });

    await createListPicker(permission_prioritize_element, {
        locale: doc.body.dataset.userLocale,
        placeholder: gettext_provider.gettext("Choose who can prioritize and plan items"),
        is_filterable: true,
    });
}

function getHTMLSelectElementFromId(doc: Document, id: string): HTMLSelectElement {
    const select_element = doc.getElementById(id);

    if (!select_element || !(select_element instanceof HTMLSelectElement)) {
        throw new Error(id + " element does not exist");
    }

    return select_element;
}
