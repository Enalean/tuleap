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
    <div
        class="draggable-wrapper"
        draggable="true"
        v-bind:data-element-id="fieldset.field.field_id"
    >
        <i
            class="fa-solid fa-grip-vertical draggable-handle"
            role="img"
            v-bind:title="$gettext('Move element')"
        ></i>
        <div class="draggable-form-element">
            <section class="tlp-pane">
                <div class="tlp-pane-container">
                    <div class="tlp-pane-header fieldset-header">
                        <h1 class="tlp-pane-title">
                            <label-for-field v-bind:field="fieldset.field" />
                        </h1>
                        <fieldset-layout v-bind:fieldset="fieldset" />
                    </div>
                    <div
                        class="tlp-pane-section tracker-admin-fields-container-dropzone"
                        v-bind:data-container-id="fieldset.field.field_id"
                    >
                        <display-form-elements
                            v-bind:elements="fieldset.children"
                            v-if="fieldset.children.length"
                        />
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import type { Fieldset } from "../../type";
import DisplayFormElements from "../DisplayFormElements.vue";
import FieldsetLayout from "./FieldsetLayout.vue";
import LabelForField from "./LabelForField.vue";

const { $gettext } = useGettext();

defineProps<{
    fieldset: Fieldset;
}>();
</script>

<style lang="scss" scoped>
.fieldset-header {
    display: flex;
    gap: var(--tlp-medium-spacing);
    justify-content: space-between;
}
</style>
