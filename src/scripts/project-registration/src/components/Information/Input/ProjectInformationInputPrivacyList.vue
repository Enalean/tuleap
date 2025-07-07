<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -->

<template>
    <select
        id="project-information-input-privacy-list-label"
        class="tlp-select"
        name="privacy"
        data-test="project-information-input-privacy-list"
        v-model="selected_visibility"
        v-on:change="onChange"
        required
        ref="element"
    >
        <option
            v-if="root_store.are_restricted_users_allowed"
            value="unrestricted"
            data-test="unrestricted"
        >
            {{ $gettext("Public incl. restricted") }}
        </option>
        <option value="public" data-test="public">{{ $gettext("Public") }}</option>
        <option value="private" data-test="private">{{ $gettext("Private") }}</option>
        <option
            value="private-wo-restr"
            v-if="root_store.are_restricted_users_allowed"
            data-test="private-wo-restr"
        >
            {{ $gettext("Private without restricted") }}
        </option>
    </select>
</template>

<script setup lang="ts">
import type { Ref } from "vue";
import { onBeforeUnmount, onMounted, ref } from "vue";
import type { ListPicker } from "@tuleap/list-picker";
import { createListPicker } from "@tuleap/list-picker";
import emitter from "../../../helpers/emitter";
import {
    ACCESS_PRIVATE,
    ACCESS_PRIVATE_WO_RESTRICTED,
    ACCESS_PUBLIC,
    ACCESS_PUBLIC_UNRESTRICTED,
} from "../../../constant";
import { useStore } from "../../../stores/root";
import { useGettext } from "vue3-gettext";

const root_store = useStore();
const element = ref<InstanceType<typeof Element>>();
const { $gettext } = useGettext();

const list_picker_instance: Ref<ListPicker | null> = ref(null);

const selected_visibility = ref(root_store.project_default_visibility);

onMounted(() => {
    setTimeout(() => {
        // wait so that the handler of the event in an ancestor component has time to register itself
        onChange();
    });
    if (!(element.value instanceof HTMLSelectElement)) {
        throw new Error("Element is supposed to be a select element");
    }
    list_picker_instance.value = createListPicker(element.value, {
        items_template_formatter: (html_processor, value_id, item_label) => {
            const description = translatedVisibilityDetails(value_id);
            const template = html_processor`<div>
                <span class="project-information-input-privacy-list-option-label">${item_label}</span>
                <p class="project-information-input-privacy-list-option-description">${description}</p>
            </div>`;
            return template;
        },
    });
});

onBeforeUnmount((): void => {
    if (list_picker_instance.value !== null) {
        list_picker_instance.value.destroy();
    }
});

function translatedVisibilityDetails(visibility: string): string {
    switch (visibility) {
        case ACCESS_PUBLIC_UNRESTRICTED:
            return $gettext(
                "Project content is available to all authenticated users, including restricted users. Please note that more restrictive permissions might exist on some items.",
            );
        case ACCESS_PUBLIC:
            return $gettext(
                "Project content is available to all authenticated users. Please note that more restrictive permissions might exist on some items.",
            );
        case ACCESS_PRIVATE:
            if (root_store.are_restricted_users_allowed) {
                return $gettext(
                    "Only project members can access project content. Restricted users can be added to the project.",
                );
            }
            return $gettext("Only project members can access project content.");
        case ACCESS_PRIVATE_WO_RESTRICTED:
            return $gettext(
                "Only project members can access project content. Restricted users can NOT be added in this project.",
            );
        default:
            throw new Error("Unable to retrieve the selected visibility type");
    }
}

function onChange(): void {
    emitter.emit("update-project-visibility", { new_visibility: selected_visibility.value });
}
</script>
