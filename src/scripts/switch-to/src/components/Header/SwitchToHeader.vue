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
            <switch-to-filter />
        </div>
        <button
            type="submit"
            class="switch-to-modal-header-legacy-search-button"
            v-if="should_button_be_displayed"
            v-translate
            data-test="legacy-search-button"
        >
            Legacy search →
        </button>
    </form>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import SwitchToFilter from "./SwitchToFilter.vue";
import { State } from "vuex-class";

@Component({
    components: { SwitchToFilter },
})
export default class SwitchToHeader extends Vue {
    @State
    private readonly filter_value!: string;

    @State
    private readonly is_search_available!: boolean;

    submit(event: Event): void {
        if (!this.should_button_be_displayed) {
            event.preventDefault();
        }
    }

    get should_button_be_displayed(): boolean {
        return this.filter_value.length > 0 && this.is_search_available;
    }
}
</script>
