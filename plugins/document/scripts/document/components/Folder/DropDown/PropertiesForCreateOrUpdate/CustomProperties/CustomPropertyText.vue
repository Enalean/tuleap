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
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div
        class="tlp-form-element"
        v-if="currentlyUpdatedItemProperty.type === 'text'"
        data-test="document-custom-property-text"
    >
        <label class="tlp-label" v-bind:for="`document-${currentlyUpdatedItemProperty.short_name}`">
            {{ currentlyUpdatedItemProperty.name }}
            <i
                class="fa-solid fa-asterisk"
                v-if="currentlyUpdatedItemProperty.is_required"
                data-test="document-custom-property-is-required"
            ></i>
        </label>
        <textarea
            class="tlp-textarea tlp-form-element"
            data-test="document-text-input"
            v-bind:id="`document-${currentlyUpdatedItemProperty.short_name}`"
            v-bind:required="currentlyUpdatedItemProperty.is_required"
            v-model="value"
        ></textarea>
    </div>
</template>

<script setup lang="ts">
import type { Property } from "../../../../../type";
import emitter from "../../../../../helpers/emitter";
import { computed } from "vue";

const props = defineProps<{ currentlyUpdatedItemProperty: Property }>();

const value = computed({
    get() {
        return String(props.currentlyUpdatedItemProperty.value);
    },
    set(value) {
        emitter.emit("update-custom-property", {
            property_short_name: props.currentlyUpdatedItemProperty.short_name,
            value: value,
        });
    },
});
</script>
