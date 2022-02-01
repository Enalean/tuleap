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
        <tr>
            <td
                colspan="7"
                class="tlp-table-cell-empty"
                data-test="search-results-table-body-empty"
                v-translate
            >
                No matching results
            </td>
        </tr>
    </tbody>
</template>
<script lang="ts">
import { Component, Vue } from "vue-property-decorator";

@Component
export default class TableBodyEmpty extends Vue {}
</script>
