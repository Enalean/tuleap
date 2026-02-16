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
    <section class="field-usage">
        <router-view name="sidebar" />
        <div>
            <refresh-after-error-modal
                v-if="refresh_after_error_fault !== null"
                v-bind:fault="refresh_after_error_fault"
            />
            <error-state v-if="has_error" />
            <h2>{{ $gettext("Fields usage") }}</h2>

            <tracker-structure v-if="tracker_root.children.length > 0" v-bind:key="key" />

            <empty-state v-if="tracker_root.children.length === 0" />
        </div>
    </section>
    <router-view name="error" />
</template>

<script setup lang="ts">
import { ref, provide } from "vue";
import { useGettext } from "vue3-gettext";
import type { StructureFormat } from "@tuleap/plugin-tracker-rest-api-types";
import EmptyState from "./EmptyState.vue";
import TrackerStructure from "./TrackerStructure.vue";
import { mapContentStructureToFields } from "../helpers/map-content-structure-to-fields";
import type { ElementWithChildren } from "../type";
import { RouterView } from "vue-router";
import ErrorState from "./ErrorState.vue";
import {
    OPEN_REFRESH_AFTER_FAULT_MODAL,
    POST_FIELD_DND_CALLBACK,
    TRACKER_ROOT,
    FIELDS,
} from "../injection-symbols";
import { strictInject } from "@tuleap/vue-strict-inject";
import RefreshAfterErrorModal from "./RefreshAfterErrorModal.vue";
import type { Fault } from "@tuleap/fault";

const { $gettext } = useGettext();

const props = defineProps<{
    structure: ReadonlyArray<StructureFormat>;
    has_error: boolean;
}>();

const fields = strictInject(FIELDS);

const tracker_root = ref<ElementWithChildren>(mapContentStructureToFields(props.structure, fields));

const key = ref(0);
const update = (): void => {
    key.value += 1;
};
const refresh_after_error_fault = ref<Fault | null>(null);

provide(TRACKER_ROOT, tracker_root);
provide(POST_FIELD_DND_CALLBACK, update);
provide(OPEN_REFRESH_AFTER_FAULT_MODAL, (fault: Fault) => {
    refresh_after_error_fault.value = fault;
});
</script>

<style lang="scss" scoped>
.field-usage {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: var(--tlp-medium-spacing);
    padding: var(--tlp-medium-spacing);
}
</style>
