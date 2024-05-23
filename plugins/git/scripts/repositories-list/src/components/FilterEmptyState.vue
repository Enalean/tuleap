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
    <section class="empty-state-page" v-if="show_filter_empty_state()">
        <h1 class="empty-state-title" data-test="empty-state">
            {{ $gettext("No matching repository") }}
        </h1>
        <p class="empty-state-text">
            {{ $gettext("No repository name matching your query has been found.") }}
        </p>
    </section>
</template>
<script lang="ts">
import { Component } from "vue-property-decorator";
import Vue from "vue";
import { Getter } from "vuex-class";

@Component
export default class FilterEmptyState extends Vue {
    @Getter
    readonly isThereAResultInCurrentFilteredList!: boolean;

    @Getter
    readonly isCurrentRepositoryListEmpty!: boolean;

    @Getter
    readonly isInitialLoadingDoneWithoutError!: boolean;

    @Getter
    readonly isFiltering!: boolean;

    show_filter_empty_state(): boolean {
        if (!this.isFiltering) {
            return false;
        }

        if (!this.isInitialLoadingDoneWithoutError) {
            return false;
        }

        return !this.isCurrentRepositoryListEmpty && !this.isThereAResultInCurrentFilteredList;
    }
}
</script>
