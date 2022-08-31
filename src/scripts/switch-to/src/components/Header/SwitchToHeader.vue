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
                <translate>Legacy search</translate>
                <i class="fas fa-long-arrow-alt-right" aria-hidden="true"></i>
            </button>
        </template>
    </form>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import SwitchToFilter from "./SwitchToFilter.vue";
import type { Modal } from "tlp";
import { useSwitchToStore } from "../../stores";
import type { SearchForm } from "../../type";

@Component({
    components: { SwitchToFilter },
})
export default class SwitchToHeader extends Vue {
    @Prop({ required: true })
    private readonly modal!: Modal | null;
    private store: ReturnType<useSwitchToStore> | null;

    getStore(): ReturnType<useSwitchToStore> {
        if (!this.store) {
            this.store = useSwitchToStore();
        }

        return this.store;
    }

    submit(event: Event): void {
        if (!this.should_button_be_displayed) {
            event.preventDefault();
        }
    }

    get search_form(): SearchForm {
        return this.getStore().search_form;
    }

    get should_button_be_displayed(): boolean {
        const store = this.getStore();
        return store.filter_value.length > 0 && store.is_search_available;
    }

    get is_special_search(): boolean {
        return this.search_form.type_of_search !== "soft";
    }
}
</script>
