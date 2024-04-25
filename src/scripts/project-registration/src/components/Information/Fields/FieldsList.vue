<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
    <div class="tlp-form-element" v-if="is_required">
        <label class="tlp-label" v-bind:for="`input-${field.group_desc_id}`">
            {{ field.desc_name }}
            <i class="fa fa-asterisk" data-test="asterisk"></i>
        </label>
        <input
            type="text"
            class="tlp-input tlp-input-large"
            v-bind:id="`input-${field.group_desc_id}`"
            v-if="field.desc_type === 'line'"
            required
            v-on:input="updateField(field.group_desc_id, $event)"
        />
        <textarea
            class="tlp-textarea tlp-textarea-large"
            v-bind:id="`textaarea-${field.group_desc_id}`"
            required
            v-else-if="field.desc_type === 'text'"
            v-on:input="updateField(field.group_desc_id, $event)"
            data-test="project-field-text"
        ></textarea>
        <p
            class="tlp-text-info"
            v-dompurify-html="field.desc_description"
            v-if="field.desc_description"
            data-test="text-info"
        ></p>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { FieldData } from "../../../type";
import EventBus from "../../../helpers/event-bus";

const props = defineProps<{
    field: FieldData;
}>();

const is_required = computed((): boolean => {
    return props.field.desc_required === "1";
});

function updateField(field_id: string, event: Event): void {
    if (
        !(event.target instanceof HTMLInputElement) &&
        !(event.target instanceof HTMLTextAreaElement)
    ) {
        return;
    }
    const value = event.target.value;
    EventBus.$emit("update-field-list", { field_id, value });
}
</script>
