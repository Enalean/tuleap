/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import { watch, ref } from "vue";
import type { Ref } from "vue";
import type { SectionsCollection } from "@/sections/SectionsCollection";
import type { ConfigurationField } from "@/sections/readonly-fields/AvailableReadonlyFields";
import { getSectionsLoader } from "@/sections/SectionsLoader";

export const watchUpdateSectionsReadonlyFields = (
    sections_collection: SectionsCollection,
    selected_fields: Ref<ConfigurationField[]>,
    documents_id: number,
    is_loading_sections: Ref<boolean>,
    is_loading_failed: Ref<boolean>,
): void => {
    watch(selected_fields, () => {
        is_loading_sections.value = true;
        getSectionsLoader(documents_id)
            .loadSections()
            .match(
                (collection) => {
                    sections_collection.replaceAll(collection.map((section) => ref(section)));
                    is_loading_sections.value = false;
                },
                () => {
                    sections_collection.replaceAll([]);
                    is_loading_sections.value = false;
                    is_loading_failed.value = true;
                },
            );
    });
};
