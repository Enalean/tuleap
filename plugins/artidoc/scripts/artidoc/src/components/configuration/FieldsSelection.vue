<!--
  - Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
    <table class="tlp-table" data-test="artidoc-configuration-fields-table">
        <thead>
            <tr>
                <th></th>
                <th>{{ $gettext("Field") }}</th>
                <th>{{ $gettext("Display") }}</th>
                <th></th>
                <th></th>
            </tr>
        </thead>
        <tbody v-if="selected_fields.length === 0">
            <tr>
                <td
                    colspan="5"
                    class="tlp-table-cell-empty"
                    data-test="readonly-fields-empty-state"
                >
                    {{ $gettext("No fields selected") }}
                </td>
            </tr>
        </tbody>
        <tbody v-else>
            <tr
                v-for="(field, index) in selected_fields"
                v-bind:key="index"
                data-test="readonly-field-rows"
            >
                <td></td>
                <td>{{ field.label }}</td>
                <td>
                    <label class="tlp-label tlp-checkbox">
                        <input
                            disabled
                            type="checkbox"
                            value="1"
                            v-bind:checked="field.display_type === 'block'"
                        />
                        {{ $gettext("Full row") }}
                    </label>
                </td>
                <td class="tlp-table-cell-actions">
                    <button
                        disabled
                        type="button"
                        class="tlp-table-cell-actions-button tlp-button-small tlp-button-danger tlp-button-outline"
                    >
                        <i class="tlp-button-icon fa-solid fa-trash fa-fw" aria-hidden="true"></i>
                        {{ $gettext("Remove") }}
                    </button>
                </td>
                <td></td>
            </tr>
        </tbody>
    </table>
</template>

<script setup lang="ts">
import type { Ref } from "vue";
import { ref } from "vue";
import { useGettext } from "vue3-gettext";
import type { ReadonlyField } from "@/sections/readonly-fields/ReadonlyFieldsCollection";

const { $gettext } = useGettext();

const props = defineProps<{
    selected_fields: ReadonlyField[];
}>();

const selected_fields: Ref<ReadonlyField[]> = ref(props.selected_fields);
</script>
