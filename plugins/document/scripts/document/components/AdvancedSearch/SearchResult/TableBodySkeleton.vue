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
        <tr v-for="i in 3" v-bind:key="i">
            <template v-for="column of columns">
                <td
                    v-bind:class="{ 'tlp-table-cell-numeric': column.name === 'id' }"
                    v-bind:key="'document-search-result-' + column.name + '-skeleton'"
                >
                    <i
                        class="fas tlp-skeleton-icon document-search-result-icon"
                        v-bind:class="{
                            'fa-file-alt': column.name === 'title',
                            'fa-user': column.name === 'owner',
                        }"
                        v-if="column.name === 'title' || column.name === 'owner'"
                        aria-hidden="true"
                    ></i>
                    <span class="tlp-skeleton-text"></span>
                </td>
            </template>
        </tr>
    </tbody>
</template>

<script setup lang="ts">
import type { ListOfSearchResultColumnDefinition } from "../../../type";

defineProps<{ columns: ListOfSearchResultColumnDefinition }>();
</script>

<script lang="ts">
import { defineComponent } from "@vue/composition-api";

export default defineComponent({});
</script>
