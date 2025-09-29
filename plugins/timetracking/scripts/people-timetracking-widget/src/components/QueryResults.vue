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
    <user-details-modal
        v-if="details_user_times"
        v-bind:user_times="details_user_times"
        v-bind:widget_id="widget_id"
        v-bind:query="query"
        ref="modal"
    />
    <table class="tlp-table">
        <thead>
            <tr>
                <th class="users">{{ $gettext("Users") }}</th>
                <th class="tlp-table-cell-numeric">{{ $gettext("Time") }}</th>
                <th class="tlp-table-cell-actions"></th>
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
import type { Query, QueryResults, UserTimes } from "../type";
import { onMounted, useTemplateRef, provide, ref, computed, nextTick } from "vue";
import QueryResultsNoUsers from "./QueryResultsNoUsers.vue";
import QueryResultsLoadingState from "./QueryResultsLoadingState.vue";
import QueryResultsErrorState from "./QueryResultsErrorState.vue";
import QueryResultsEmptyState from "./QueryResultsEmptyState.vue";
import { getTimes } from "../api/rest-querier";
import QueryResultsRows from "./QueryResultsRows.vue";
import UserDetailsModal from "./UserDetailsModal.vue";
import { OPEN_MODAL_DETAILS } from "../injection-symbols";

const props = defineProps<{
    query: Query;
    widget_id: number;
}>();

const { $gettext } = useGettext();

const is_loading = ref(true);
const error_message = ref("");
const results = ref<QueryResults>([]);
const nb_results = computed(() => results.value.length);
const nb_users = computed(() => props.query.users_list.length);

const details_user_times = ref<UserTimes | null>(null);
const modal = useTemplateRef<InstanceType<typeof UserDetailsModal>>("modal");
provide(OPEN_MODAL_DETAILS, (times: UserTimes): void => {
    details_user_times.value = times;

    // on the first open, we need to defer the opening of the modal because the component is not yet mounted, due to the v-if
    nextTick(() => modal.value?.show());
});

onMounted(() => {
    if (nb_users.value === 0) {
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
.dashboard-widget-content-people-timetracking-widget {
    border-radius: var(--tlp-large-radius);
}
</style>

<style lang="scss" scoped>
table {
    margin: 0 0 var(--tlp-medium-spacing);
}

.users,
.tlp-table-cell-actions {
    width: 50%;
}
</style>
