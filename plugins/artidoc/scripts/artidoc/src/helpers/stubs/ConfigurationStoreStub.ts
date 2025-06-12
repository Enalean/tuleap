/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import { ref } from "vue";
import type { Tracker } from "@/configuration/AllowedTrackersCollection";
import type { ConfigurationStore } from "@/stores/configuration-store";
import { TrackerStub } from "@/helpers/stubs/TrackerStub";
import { noop } from "@/helpers/noop";
import type { ConfigurationField } from "@/sections/readonly-fields/AvailableReadonlyFields";

const tasks: Tracker = {
    ...TrackerStub.withoutTitleAndDescription(),
    id: 101,
    label: "Tasks",
};

const bugs: Tracker = {
    ...TrackerStub.withoutTitleAndDescription(),
    id: 102,
    label: "Bugs",
};

export const ConfigurationStoreStub = {
    tasks,
    bugs,
    buildEmpty: (): ConfigurationStore => ({
        selected_fields: ref([]),
        available_fields: ref([]),
        is_saving: ref(false),
        is_error: ref(false),
        is_success: ref(false),
        error_message: ref(""),
        saveTrackerConfiguration: noop,
        saveFieldsConfiguration: noop,
        resetSuccessFlagFromPreviousCalls: noop,
        current_project: ref(null),
    }),

    withSuccessfulSave: (): ConfigurationStore => ({
        ...ConfigurationStoreStub.buildEmpty(),
        is_success: ref(true),
    }),

    withError: (): ConfigurationStore => ({
        ...ConfigurationStoreStub.buildEmpty(),
        is_error: ref(true),
        error_message: ref("Oh no!"),
    }),

    withSelectedFields: (selected_fields: ConfigurationField[]): ConfigurationStore => ({
        ...ConfigurationStoreStub.buildEmpty(),
        selected_fields: ref(selected_fields),
    }),
};
