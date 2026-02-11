<!--
  - Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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
    <sidebar-container>
        <div class="tlp-pane-container">
            <div class="tlp-pane-header">
                <h1 class="tlp-pane-title">{{ title }}</h1>
            </div>
            <div class="tlp-pane-section">
                <not-found v-if="field === undefined" />
                <field-edition-body v-else v-bind:field="field" />
            </div>
        </div>
    </sidebar-container>
</template>

<script setup lang="ts">
import { computed } from "vue";
import SidebarContainer from "../SidebarContainer.vue";
import { FIELDS } from "../../../injection-symbols";
import { strictInject } from "@tuleap/vue-strict-inject";
import { useGettext } from "vue3-gettext";
import FieldEditionBody from "./FieldEditionBody.vue";
import { CONTAINER_FIELDSET } from "@tuleap/plugin-tracker-constants";
import NotFound from "../../NotFound.vue";

const { $gettext } = useGettext();

const props = defineProps<{ field_id: number }>();

const fields = strictInject(FIELDS);

const field = computed(() =>
    fields.find((field) => {
        return field.field_id === props.field_id;
    }),
);

const title = computed(() =>
    field.value?.type === CONTAINER_FIELDSET
        ? $gettext("Fieldset configuration")
        : $gettext("Field configuration"),
);
</script>
