<!--
  - Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
    <table class="tlp-table document-search-result">
        <thead>
            <tr>
                <th class="tlp-table-cell-numeric" v-translate>Id</th>
                <th v-translate>Title</th>
                <th v-translate>Description</th>
                <th v-translate>Owner</th>
                <th v-translate>Update date</th>
                <th v-translate>Location</th>
            </tr>
        </thead>
        <table-body-skeleton v-if="is_loading" />
        <table-body-empty v-else />
    </table>
</template>
<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import TableBodySkeleton from "./TableBodySkeleton.vue";
import TableBodyEmpty from "./TableBodyEmpty.vue";

@Component({
    components: { TableBodyEmpty, TableBodySkeleton },
})
export default class SearchResultTable extends Vue {
    @Prop({ required: true })
    readonly is_loading!: boolean;
}
</script>
