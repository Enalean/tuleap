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
    <input
        class="tlp-search"
        autocomplete="off"
        v-bind:placeholder="$gettext('Repository name')"
        type="search"
        v-model="filter_value"
        size="30"
    />
</template>
<script lang="ts">
import { Component, Watch } from "vue-property-decorator";
import Vue from "vue";
import { State } from "vuex-class";

@Component
export default class ListFilter extends Vue {
    @State
    readonly filter!: string;

    filter_value: string | null = null;

    mounted(): void {
        this.filter_value = this.filter;
    }

    @Watch("filter_value")
    public updateFilter(value: string) {
        this.$store.commit("setFilter", value);
    }
}
</script>
