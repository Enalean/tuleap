<!--
  - Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
  -
  -->

<template>
    <div class="document-history-logs">
        <h2 class="document-history-section-title">
            {{ $gettext("Logs") }}
        </h2>
        <section class="tlp-pane">
            <div class="tlp-pane-container">
                <section class="tlp-pane-section">
                    <table class="tlp-table" data-test="table-test">
                        <thead>
                            <tr>
                                <th>{{ $gettext("When") }}</th>
                                <th>{{ $gettext("Who") }}</th>
                                <th>{{ $gettext("What") }}</th>
                                <th>{{ $gettext("Old value") }}</th>
                                <th>{{ $gettext("New value") }}</th>
                            </tr>
                        </thead>

                        <history-logs-loading-state v-if="is_loading" />
                        <history-logs-error-state v-else-if="is_in_error" v-bind:colspan="5" />
                        <history-logs-empty-state v-else-if="is_empty" v-bind:colspan="5" />
                        <history-logs-content v-else v-bind:log_entries="log_entries" />
                    </table>
                </section>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import type { Item } from "../../type";
import { computed, onMounted, ref } from "vue";
import type { LogEntry } from "../../api/log-rest-querier";
import { getLogs } from "../../api/log-rest-querier";

import HistoryLogsLoadingState from "./HistoryLogsLoadingState.vue";
import HistoryLogsErrorState from "./HistoryLogsErrorState.vue";
import HistoryLogsEmptyState from "./HistoryLogsEmptyState.vue";
import HistoryLogsContent from "./HistoryLogsContent.vue";

const props = defineProps<{ item: Item }>();

const is_loading = ref(true);
const is_in_error = ref(false);
const log_entries = ref<readonly LogEntry[]>([]);
const is_empty = computed((): boolean => log_entries.value.length === 0);

onMounted(() => {
    getLogs(props.item.id).match(
        (log: readonly LogEntry[]): void => {
            log_entries.value = log;
            is_loading.value = false;
        },
        (): void => {
            is_in_error.value = true;
            is_loading.value = false;
        },
    );
});
</script>
