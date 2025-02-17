<!--
  - Copyright (c) Enalean, 2025-present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
  -
  -  Tuleap is free software; you can redistribute it and/or modify
  -  it under the terms of the GNU General Public License as published by
  -  the Free Software Foundation; either version 2 of the License, or
  -  (at your option) any later version.
  -
  -  Tuleap is distributed in the hope that it will be useful,
  -  but WITHOUT ANY WARRANTY; without even the implied warranty of
  -  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  -  GNU General Public License for more details.
  -
  -  You should have received a copy of the GNU General Public License
  -  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="tlp-dropdown">
        <button
            type="button"
            class="tlp-button-primary tlp-button-outline tlp-button-mini"
            ref="dropdown_trigger"
        >
            {{ $gettext("Choose another query") }}
            <i class="fa-solid fa-caret-down tlp-button-icon-right" aria-hidden="true"></i>
        </button>
        <div class="tlp-dropdown-menu dropdown-menu-filter" role="menu" ref="dropdown_menu">
            <div class="tlp-dropdown-menu-actions">
                <input
                    type="search"
                    class="tlp-search tlp-search-small"
                    v-bind:placeholder="$gettext('Search')"
                    v-on:input="updateFilter"
                    ref="filter_element"
                    data-test="query-filter"
                />
            </div>
            <div
                v-for="query in filtered_queries"
                v-bind:key="query.uuid"
                v-bind:title="query.description"
                v-bind:class="{ 'current-query': query.uuid === current_query?.uuid }"
                class="tlp-dropdown-menu-item"
                role="menuitem"
                v-on:click.prevent="updateSelectedQuery(query)"
                data-test="query"
            >
                {{ query.title }}
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import type { Dropdown } from "@tuleap/tlp-dropdown";
import { createDropdown } from "@tuleap/tlp-dropdown";
import { EMITTER } from "../../injection-symbols";
import type { ReadingCrossTrackerReport } from "../../domain/ReadingCrossTrackerReport";
import type { WritingCrossTrackerReport } from "../../domain/WritingCrossTrackerReport";
import type { Report } from "../../type";
import { strictInject } from "@tuleap/vue-strict-inject";
import { REFRESH_ARTIFACTS_EVENT, SWITCH_QUERY_EVENT } from "../../helpers/emitter-provider";

const dropdown_trigger = ref<HTMLElement>();
const dropdown_menu = ref<HTMLElement>();
let dropdown: Dropdown | null = null;

const emitter = strictInject(EMITTER);

const props = defineProps<{
    writing_cross_tracker_report: WritingCrossTrackerReport;
    reading_cross_tracker_report: ReadingCrossTrackerReport;
    queries: ReadonlyArray<Report>;
    selected_query: Report | null;
}>();

const current_query = ref<Report | null>(null);

const filter_input = ref("");
const filter_element = ref<InstanceType<typeof HTMLInputElement>>();

const filtered_queries = computed(
    (): ReadonlyArray<Report> =>
        props.queries.filter(
            (query: Report) =>
                query.title.toLowerCase().indexOf(filter_input.value.toLowerCase()) !== -1,
        ),
);

onMounted((): void => {
    if (dropdown_trigger.value && dropdown_menu.value) {
        dropdown = createDropdown(dropdown_trigger.value, {
            trigger: "click",
            dropdown_menu: dropdown_menu.value,
            keyboard: true,
        });
    }
});

function updateFilter(event: Event): void {
    const event_target = event.currentTarget;
    if (event_target instanceof HTMLInputElement) {
        filter_input.value = event_target.value;
    }
}

function updateSelectedQuery(query: Report): void {
    props.writing_cross_tracker_report.setExpertQuery(query.expert_query);
    props.reading_cross_tracker_report.setNewQuery(query.expert_query);
    emitter.emit(REFRESH_ARTIFACTS_EVENT, { query });
    emitter.emit(SWITCH_QUERY_EVENT);
    current_query.value = query;
    resetFilter();
    dropdown?.hide();
}

function resetFilter(): void {
    if (filter_element.value instanceof HTMLInputElement) {
        filter_input.value = "";
        filter_element.value.value = "";
    }
}
onBeforeUnmount(() => {
    dropdown?.destroy();
});
</script>

<style lang="scss" scoped>
.current-query {
    opacity: 0.5;
    background-color: var(--tlp-main-color-hover-background);
    pointer-events: none;
}
</style>
