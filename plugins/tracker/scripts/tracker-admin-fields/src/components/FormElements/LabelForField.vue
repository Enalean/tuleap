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
  -->

<template>
    <div class="label-container">
        <div class="label">
            <span class="tlp-label" v-if="should_label_be_displayed">
                {{ field.label }}
                <i
                    class="fa-solid fa-asterisk"
                    aria-hidden="true"
                    v-if="field.required"
                    data-test="required"
                ></i>
            </span>
            <list-of-label-decorators v-bind:field="field" />
        </div>
        <router-link
            v-bind:to="{ name: 'field-edition', params: { field_id: field.field_id } }"
            class="tlp-button-primary tlp-button-mini edit-button"
            data-not-drag-handle="true"
        >
            <i class="fa-solid fa-pencil tlp-button-icon" aria-hidden="true"></i>
            {{ $gettext("Edit") }}
        </router-link>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { StructureFields } from "@tuleap/plugin-tracker-rest-api-types";
import ListOfLabelDecorators from "./ListOfLabelDecorators.vue";
import { LINE_BREAK, SEPARATOR, STATIC_RICH_TEXT } from "@tuleap/plugin-tracker-constants";

const props = defineProps<{
    field: StructureFields;
}>();

const should_label_be_displayed = computed(
    () => [LINE_BREAK, SEPARATOR, STATIC_RICH_TEXT].includes(props.field.type) === false,
);
</script>

<style lang="scss" scoped>
.label-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 0 0 var(--tlp-small-spacing);
    gap: var(--tlp-medium-spacing);
}

.fieldset-title > .label-container {
    margin: 0;
}

.label {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--tlp-small-spacing);
}

.tlp-label {
    margin: 0 var(--tlp-small-spacing) 0 0;
}

.edit-button {
    transition: opacity 250ms ease-in-out;
    opacity: 0;

    &:focus {
        opacity: 1;
    }
}

.draggable-wrapper > .draggable-form-element > .tlp-property:hover,
.draggable-wrapper > .draggable-form-element > .tlp-form-element:hover,
.tlp-pane:hover > .tlp-pane-container > .tlp-pane-header > .tlp-pane-title {
    /* stylelint-disable-next-line selector-max-compound-selectors */
    > .label-container > .edit-button {
        opacity: 1;
    }
}
</style>
