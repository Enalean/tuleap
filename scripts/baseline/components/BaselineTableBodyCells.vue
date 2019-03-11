<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
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
    <tbody>
        <tr v-for="baseline in baselines" v-bind:key="baseline.id" data-test-type="baseline">
            <td class="tlp-table-cell-numeric">
                {{ baseline.id }}
            </td>
            <td>{{ baseline.name }}</td>
            <td>{{ baseline.snapshot_date }}</td>
            <td>{{ baseline.author.username }}</td>
            <td class="tlp-table-cell-actions baselines-table-column-actions">
                <button
                    v-on:click="goTo(`baselines/${baseline.id}`)"
                    class="tlp-button-small tlp-button-primary tlp-button-outline"
                >
                    Consult
                </button>
            </td>
        </tr>
    </tbody>
</template>

<script>
export default {
    name: "BaselineTableBodyCells",

    props: {
        baselines: { required: true, type: Array }
    }
};
</script>
