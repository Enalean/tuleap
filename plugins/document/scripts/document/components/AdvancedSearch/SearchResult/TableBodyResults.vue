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
    <tbody>
        <table-body-result-row v-for="item in results" v-bind:key="item.id" v-bind:item="item" />
    </tbody>
</template>
<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import type { ItemSearchResult } from "../../../type";
import TableBodyResultRow from "./TableBodyResultRow.vue";

@Component({
    components: { TableBodyResultRow },
})
export default class TableBodyResults extends Vue {
    @Prop({ required: true })
    readonly results!: ReadonlyArray<ItemSearchResult>;
}
</script>
