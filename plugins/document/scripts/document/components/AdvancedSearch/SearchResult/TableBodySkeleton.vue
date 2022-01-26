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
            <td class="tlp-table-cell-numeric"><span class="tlp-skeleton-text"></span></td>
            <td class="document-search-result-icon">
                <i class="fas fa-file-alt tlp-skeleton-icon" aria-hidden="true"></i>
            </td>
            <td><span class="tlp-skeleton-text"></span></td>
            <td><span class="tlp-skeleton-text"></span></td>
            <td>
                <i class="fas fa-user tlp-skeleton-icon" aria-hidden="true"></i>
                <span class="tlp-skeleton-text"></span>
            </td>
            <td><span class="tlp-skeleton-text"></span></td>
            <td><span class="tlp-skeleton-text"></span></td>
        </tr>
    </tbody>
</template>
<script lang="ts">
import { Component, Vue } from "vue-property-decorator";

@Component
export default class TableBodySkeleton extends Vue {}
</script>
