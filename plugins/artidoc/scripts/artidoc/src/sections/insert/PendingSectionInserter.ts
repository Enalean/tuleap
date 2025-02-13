/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

import { ref, watch } from "vue";
import type { Ref } from "vue";
import type { Tracker } from "@/stores/configuration-store";
import { isTrackerWithSubmittableSection } from "@/stores/configuration-store";
import type { SectionsCollection } from "@/sections/SectionsCollection";
import { injectInternalId } from "@/helpers/inject-internal-id";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import type { SectionsStatesCollection } from "@/sections/states/SectionsStatesCollection";

export const watchForNeededPendingSectionInsertion = (
    sections_collection: SectionsCollection,
    states_collection: SectionsStatesCollection,
    tracker: Ref<Tracker | null>,
    can_user_edit_document: boolean,
): void => {
    if (!can_user_edit_document) {
        return;
    }

    const insertPendingSectionForEmptyDocument = (): void => {
        if (sections_collection.sections.value.length > 0 || !tracker.value) {
            return;
        }

        const selected_tracker = tracker.value;
        const is_configured_tracker_valid = isTrackerWithSubmittableSection(selected_tracker);
        const section = ref(
            is_configured_tracker_valid
                ? injectInternalId(
                      PendingArtifactSectionFactory.overrideFromTracker(selected_tracker),
                  )
                : injectInternalId(FreetextSectionFactory.pending()),
        );

        states_collection.createStateForSection(section);
        sections_collection.sections.value.push(ref(section));
    };

    watch(
        () => sections_collection.sections.value.length === 0,
        (is_document_empty: boolean) => {
            if (!is_document_empty) {
                return;
            }

            insertPendingSectionForEmptyDocument();
        },
    );

    watch(
        () => tracker.value,
        (old_value, new_value) => {
            if (sections_collection.sections.value.length > 0) {
                return;
            }

            if (old_value === null && new_value !== null) {
                return;
            }

            insertPendingSectionForEmptyDocument();
        },
    );
};
