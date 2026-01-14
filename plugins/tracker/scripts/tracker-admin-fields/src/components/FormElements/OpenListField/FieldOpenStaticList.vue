<!--
  - Copyright (c) Enalean, 2026-present. All Rights Reserved.
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
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="tlp-form-element">
        <label-for-field v-bind:field="field" />
        <tuleap-lazybox ref="open_static_list_lazybox" />
    </div>
</template>

<script setup lang="ts">
import type { StaticBoundOpenListField } from "@tuleap/plugin-tracker-rest-api-types";
import type { Lazybox, LazyboxItem } from "@tuleap/lazybox";
import { onMounted, ref } from "vue";
import { useGettext } from "vue3-gettext";
import LabelForField from "../LabelForField.vue";
import type { GroupOfItems } from "@tuleap/lazybox/src/GroupCollection";
import { getTemplateContent } from "./open-static-list-template-getter";
import {
    buildStaticValueForLazyboxFromNewValueName,
    buildStaticValueForLazyboxFromStaticListItem,
} from "./open-static-list-new-value-builder";
import { handleStaticListValueFilter } from "./open-static-list-filter-handler";

const props = defineProps<{
    field: StaticBoundOpenListField;
}>();

const open_static_list_lazybox = ref<Lazybox & HTMLElement>();

const { $gettext, interpolate } = useGettext();

const values = props.field.values
    .filter((value) => !value.is_hidden)
    .map((list_value) => {
        return buildStaticValueForLazyboxFromStaticListItem(list_value);
    });

let selected_values: LazyboxItem[] = [];

const group_of_values: GroupOfItems[] = [
    {
        label: $gettext("Static values"),
        empty_message: $gettext("No value"),
        is_loading: false,
        items: values,
        footer_message: "",
    },
];

onMounted(() => {
    if (open_static_list_lazybox.value === undefined) {
        return;
    }

    initOpenListInput(open_static_list_lazybox.value);
});

function initOpenListInput(lazybox: Lazybox & HTMLElement): void {
    lazybox.options = {
        is_multiple: true,
        placeholder: $gettext("Add a value"),
        new_item_label_callback: (item_name): string =>
            item_name !== ""
                ? interpolate($gettext("→ Create a new value %{ name }…"), { name: item_name })
                : $gettext("→ Create a new value…"),
        new_item_clicked_callback: (item_name: string): void => {
            if (item_name.trim() === "") {
                return;
            }
            const new_value = buildStaticValueForLazyboxFromNewValueName(item_name);
            selected_values.push(new_value);
            values.push(new_value);

            lazybox.replaceSelection(selected_values);
            lazybox.replaceDropdownContent(group_of_values);
        },
        templating_callback: getTemplateContent,
        selection_callback: (selection: unknown[]): void => {
            selected_values = selection.reduce((accumulator: LazyboxItem[], current) => {
                const found_value = values.find((current_value) => current_value.value === current);
                if (found_value !== undefined) {
                    accumulator.push(found_value);
                }
                return accumulator;
            }, []);
        },
        search_input_callback: (query: string): void => {
            lazybox.replaceDropdownContent(
                handleStaticListValueFilter(query, values, {
                    $gettext,
                }),
            );
        },
    };
    lazybox.replaceDropdownContent(group_of_values);
}
</script>
