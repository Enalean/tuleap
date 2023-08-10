<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <div>
        <ul class="move-artifact-display-more-fields-message">
            <li
                v-for="field in fields_to_display"
                v-bind:key="field.field_id"
                data-test="field-label"
            >
                {{ field.label }}
            </li>
        </ul>
        <button
            v-on:click="is_minimal_display = false"
            v-if="is_minimal_display && fields.length > 5"
            v-bind:class="show_more_classes"
            data-test="show-more-fields-button"
        >
            {{ $gettext("Show more") }}
        </button>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { useGettext } from "vue3-gettext";
import type { ArtifactField } from "../api/types";
import type { DryRunFieldsType } from "../types";

const { $gettext } = useGettext();

const props = defineProps<{
    readonly fields: ArtifactField[];
    readonly type: DryRunFieldsType;
}>();

const is_minimal_display = ref(true);
const fields_to_display = computed((): ArtifactField[] =>
    is_minimal_display.value === true ? props.fields.slice(0, 5) : props.fields
);
const show_more_classes = ref(
    `btn btn-link move-artifact-display-more-field move-artifact-display-more-field-${props.type}`
);
</script>
