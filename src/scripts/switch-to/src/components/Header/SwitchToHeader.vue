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
            <i class="fa fa-search tlp-modal-title-icon switch-to-modal-header-icon"></i>
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
import { State } from "vuex-class";
import { SearchForm } from "../../type";
import { Modal } from "tlp";

@Component({
    components: { SwitchToFilter },
})
export default class SwitchToHeader extends Vue {
    @Prop({ required: true })
    private readonly modal!: Modal | null;

    @State
    private readonly filter_value!: string;

    @State
    private readonly is_search_available!: boolean;

    @State
    private readonly search_form!: SearchForm;

    submit(event: Event): void {
        if (!this.should_button_be_displayed) {
            event.preventDefault();
        }
    }

    get should_button_be_displayed(): boolean {
        return this.filter_value.length > 0 && this.is_search_available;
    }

    get is_special_search(): boolean {
        return this.search_form.type_of_search !== "soft";
    }
}
</script>
