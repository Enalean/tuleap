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
    <section class="tlp-pane-section">
        <div class="tlp-alert-danger" v-if="has_error">
            {{ error }}
        </div>

        <div class="permission-per-group-load-button" v-if="display_button_load_all">
            <button class="tlp-button-primary tlp-button-outline" v-on:click="loadAll()">
                {{ $gettext("See all tracker permissions") }}
            </button>
        </div>

        <div class="permission-per-group-loader" v-if="is_loading"></div>

        <tracker-permissions-table
            v-if="is_loaded"
            v-bind:tracker_permissions="permissions"
            v-bind:selected_ugroup_name="selected_ugroup_name"
        />
    </section>
</template>
<script setup lang="ts">
import type { TrackerPermissions } from "./rest-querier.js";
import { getTrackerPermissions } from "./rest-querier.js";
import TrackerPermissionsTable from "./TrackerPermissionsTable.vue";
import { computed, ref } from "vue";
import { useGettext } from "vue3-gettext";

const props = defineProps<{
    selected_project_id: string;
    selected_ugroup_id: string;
    selected_ugroup_name: string;
}>();

const { $gettext } = useGettext();

const permissions = ref<TrackerPermissions>([]);
const error = ref<string | null>(null);
const is_loaded = ref(false);
const is_loading = ref(false);
const has_error = computed(() => error.value !== null);
const display_button_load_all = computed(() => !is_loaded.value && !is_loading.value);

function loadAll(): void {
    is_loading.value = true;
    getTrackerPermissions(props.selected_project_id, props.selected_ugroup_id).match(
        (tracker_permissions: TrackerPermissions) => {
            is_loaded.value = true;
            is_loading.value = false;
            permissions.value = tracker_permissions;
        },
        (fault) => {
            is_loading.value = false;
            error.value = String(fault);
        },
    );
}
</script>
