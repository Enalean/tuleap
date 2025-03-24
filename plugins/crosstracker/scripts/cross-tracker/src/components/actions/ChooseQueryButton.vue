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
            data-test="choose-query-button"
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
                <button
                    v-if="is_user_admin"
                    class="tlp-button-primary tlp-button-small"
                    v-bind:title="$gettext('Query creation is under implementation')"
                    v-on:click="handleCreateNewQueryButton()"
                    data-test="query-create-new-button"
                >
                    <span>
                        <i class="fa-solid fa-plus tlp-button-icon" aria-hidden="true"></i>
                        {{ $gettext("Create new query") }}</span
                    >
                </button>
            </div>
            <div
                v-for="query in filtered_queries"
                v-bind:key="query.id"
                v-bind:title="query.description"
                v-bind:class="{ 'current-query': query.id === backend_query.id }"
                class="tlp-dropdown-menu-item dropdown-item"
                role="menuitem"
                v-on:click.prevent="updateSelectedQuery(query)"
                data-test="query"
            >
                {{ query.title }}
                <span v-if="query.is_default" class="tlp-text-muted">{{
                    $gettext("Default")
                }}</span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import type { Dropdown } from "@tuleap/tlp-dropdown";
import { createDropdown } from "@tuleap/tlp-dropdown";
import { EMITTER, IS_USER_ADMIN } from "../../injection-symbols";
import type { Query } from "../../type";
import { strictInject } from "@tuleap/vue-strict-inject";
import {
    CREATE_NEW_QUERY_EVENT,
    REFRESH_ARTIFACTS_EVENT,
    SWITCH_QUERY_EVENT,
} from "../../helpers/widget-events";

const dropdown_trigger = ref<HTMLElement>();
const dropdown_menu = ref<HTMLElement>();
let dropdown: Dropdown | null = null;

const emitter = strictInject(EMITTER);
const is_user_admin = strictInject(IS_USER_ADMIN);

const props = defineProps<{
    backend_query: Query;
    queries: ReadonlyArray<Query>;
}>();

const filter_input = ref("");
const filter_element = ref<InstanceType<typeof HTMLInputElement>>();

const filtered_queries = computed(
    (): ReadonlyArray<Query> =>
        props.queries.filter(
            (query: Query) =>
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

function updateSelectedQuery(query: Query): void {
    emitter.emit(REFRESH_ARTIFACTS_EVENT, { query });
    emitter.emit(SWITCH_QUERY_EVENT, { query });
    resetFilter();
    dropdown?.hide();
}

function resetFilter(): void {
    if (filter_element.value instanceof HTMLInputElement) {
        filter_input.value = "";
        filter_element.value.value = "";
    }
}

function handleCreateNewQueryButton(): void {
    emitter.emit(CREATE_NEW_QUERY_EVENT);
}

onBeforeUnmount(() => {
    dropdown?.destroy();
});
</script>

<style lang="scss" scoped>
.dropdown-item {
    display: flex;
    justify-content: space-between;
}

.current-query {
    opacity: 0.5;
    background-color: var(--tlp-main-color-hover-background);
    pointer-events: none;
}

.dropdown-menu-filter {
    min-width: 400px;

    > .tlp-dropdown-menu-actions > input {
        width: 50%;
    }
}
</style>
