<!--
  - Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
    <section class="tlp-pane">
        <div class="tlp-pane-container">
            <div class="tlp-pane-header">
                <h1 class="tlp-pane-title">
                    {{ field.label }}
                </h1>
            </div>
            <div class="tlp-pane-section">
                <component
                    v-for="child of children"
                    v-bind:key="child.field.field_id"
                    v-bind:is="getComponentForField(child.field)"
                    v-bind:field="child.field"
                    v-bind:content="child.content"
                    v-bind:fields="fields"
                />
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type {
    ContainerFieldStructure,
    StructureFormat,
    TrackerResponseNoInstance,
} from "@tuleap/plugin-tracker-rest-api-types";
import { mapContentStructureToFields } from "../../helpers/map-content-structure-to-fields";
import { getComponentForField } from "../../helpers/get-component-for-field";

const props = defineProps<{
    field: ContainerFieldStructure;
    content: StructureFormat["content"];
    fields: TrackerResponseNoInstance["fields"];
}>();

const children = computed(() => mapContentStructureToFields(props.content, props.fields));
</script>
