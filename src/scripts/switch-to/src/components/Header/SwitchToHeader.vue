<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <form class="switch-to-modal-header" action="/search/" method="GET" v-on:submit="submit">
        <div class="switch-to-modal-header-filter-container">
            <switch-to-filter v-bind:modal="modal" />
        </div>
        <template v-if="should_button_be_displayed">
            <input
                type="hidden"
                v-if="is_special_search"
                name="type_of_search"
                v-bind:value="search_form.type_of_search"
            />
            <input
                type="hidden"
                v-for="field of search_form.hidden_fields"
                v-bind:key="field.name"
                v-bind:name="field.name"
                v-bind:value="field.value"
            />
            <button
                type="submit"
                class="switch-to-modal-header-legacy-search-button"
                data-test="legacy-search-button"
            >
                {{ $gettext("Legacy search") }}
                <i
                    class="fas fa-long-arrow-alt-right switch-to-modal-header-legacy-search-button-icon"
                    aria-hidden="true"
                ></i>
            </button>
        </template>
    </form>
</template>
<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import SwitchToFilter from "./SwitchToFilter.vue";
import type { Modal } from "@tuleap/tlp-modal";
import { useRootStore } from "../../stores/root";
import { computed, inject } from "vue";
import type { SearchForm } from "../../type";
import { IS_SEARCH_AVAILABLE, SEARCH_FORM } from "../../injection-keys";

const { $gettext } = useGettext();

defineProps<{ modal: Modal | null }>();
const store = useRootStore();

const search_form = inject<SearchForm>(SEARCH_FORM, {
    type_of_search: "",
    hidden_fields: [],
});

const is_search_available = inject<boolean>(IS_SEARCH_AVAILABLE, false);

const should_button_be_displayed = computed((): boolean => {
    return is_search_available && store.is_in_search_mode;
});

const is_special_search = computed((): boolean => {
    return search_form.type_of_search !== "soft";
});

function submit(event: Event): void {
    if (!should_button_be_displayed.value) {
        event.preventDefault();
    }
}
</script>
