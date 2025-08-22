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
    <table class="tlp-table">
        <thead>
            <tr>
                <th class="users">{{ $gettext("Users") }}</th>
                <th class="tlp-table-cell-numeric">{{ $gettext("Time") }}</th>
                <th class="actions"></th>
            </tr>
        </thead>
        <query-results-no-users v-if="nb_users === 0" />
        <template v-else>
            <query-results-loading-state v-if="is_loading" v-bind:nb_users="nb_users" />
            <template v-else>
                <query-results-error-state
                    v-if="error_message !== ''"
                    v-bind:error_message="error_message"
                />
                <template v-else>
                    <query-results-empty-state v-if="nb_results === 0" />
                    <query-results-rows v-else v-bind:results="results" />
                </template>
            </template>
        </template>
    </table>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import type { QueryResults } from "../type";
import { onMounted, ref, computed } from "vue";
import QueryResultsNoUsers from "./QueryResultsNoUsers.vue";
import QueryResultsLoadingState from "./QueryResultsLoadingState.vue";
import QueryResultsErrorState from "./QueryResultsErrorState.vue";
import QueryResultsEmptyState from "./QueryResultsEmptyState.vue";
import { getTimes } from "../api/rest-querier";
import QueryResultsRows from "./QueryResultsRows.vue";

const props = defineProps<{
    nb_users: number;
    widget_id: number;
}>();

const { $gettext } = useGettext();

const is_loading = ref(true);
const error_message = ref("");
const results = ref<QueryResults>([]);
const nb_results = computed(() => results.value.length);

onMounted(() => {
    if (props.nb_users === 0) {
        return;
    }

    getTimes(props.widget_id).match(
        (res: QueryResults) => {
            results.value = res;
            is_loading.value = false;
        },
        (fault) => {
            error_message.value = $gettext(
                "Error while retrieving user times: %{error}",
                { error: String(fault) },
                true,
            );
            is_loading.value = false;
        },
    );
});
</script>

<style lang="scss">
.dashboard-widget-content-timetracking-management-widget {
    border-radius: var(--tlp-large-radius);
}
</style>

<style lang="scss" scoped>
table {
    margin: 0 0 var(--tlp-medium-spacing);
}

.users,
.actions {
    width: 50%;
}
</style>
