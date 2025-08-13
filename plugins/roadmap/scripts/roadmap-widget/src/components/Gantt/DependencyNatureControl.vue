<!--
  - Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
  -
  -->

<template>
    <div
        class="tlp-form-element roadmap-gantt-control"
        v-bind:class="{ 'tlp-form-element-disabled': is_form_element_disabled }"
    >
        <label class="tlp-label roadmap-gantt-control-label" v-bind:for="id">
            {{ $gettext("Links") }}
        </label>
        <select
            class="tlp-select tlp-select-small tlp-select-adjusted"
            v-bind:id="id"
            v-on:change="onchange"
            data-test="select-links"
            v-bind:disabled="is_select_disabled"
            v-bind:title="title"
        >
            <option
                v-bind:value="NONE_SPECIALVALUE"
                v-bind:selected="value === null"
                data-test="option-none"
            >
                {{ $gettext("None") }}
            </option>
            <option
                v-for="nature of sorted_natures"
                v-bind:key="nature"
                v-bind:value="nature"
                v-bind:selected="value === nature"
                v-bind:data-test="'option-' + nature"
            >
                {{ available_natures.get(nature) }}
            </option>
        </select>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useNamespacedGetters } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";
import { getUniqueId } from "../../helpers/uniq-id-generator";
import type { NaturesLabels } from "../../type";

const gettext_provider = useGettext();

const NONE_SPECIALVALUE = "-1";

const { has_at_least_one_row_shown } = useNamespacedGetters("tasks", [
    "has_at_least_one_row_shown",
]);

const props = defineProps<{
    value?: string | null;
    available_natures: NaturesLabels;
}>();

const emit = defineEmits<{
    (e: "input", value: string | null): void;
}>();

const id = computed(() => getUniqueId("roadmap-gantt-links"));
const sorted_natures = computed(() =>
    Array.from(props.available_natures.keys()).sort((a, b) => a.localeCompare(b)),
);
const is_form_element_disabled = computed(() => !has_at_least_one_row_shown.value);
const is_select_disabled = computed(
    () => is_form_element_disabled.value || props.available_natures.size <= 0,
);
const title = computed(() =>
    is_select_disabled.value
        ? gettext_provider.$gettext("Displayed artifacts don't have any links to each other.")
        : "",
);

function onchange($event: Event): void {
    if ($event.target instanceof HTMLSelectElement) {
        let value: string | null = $event.target.value;
        if (value === NONE_SPECIALVALUE) {
            value = null;
        }

        emit("input", value);
    }
}
</script>
