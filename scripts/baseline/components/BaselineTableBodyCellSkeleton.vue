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
    <tr>
        <td class="tlp-table-cell-numeric">
            <span class="tlp-skeleton-text"></span>
        </td>
        <td class="baselines-table-column-name">
            <span class="tlp-skeleton-text"></span>
        </td>
        <td class="baselines-table-column-milestone">
            <span class="tlp-skeleton-text"></span>
        </td>
        <td class="baselines-table-column-snapshot-date">
            <span class="tlp-skeleton-text"></span>
        </td>
        <td class="baselines-table-column-author">
            <span class="tlp-skeleton-text"></span>
        </td>
        <td class="tlp-table-cell-actions">
            <baseline-button icon="eye" v-bind:disabled="true">
                <span v-translate>
                    Consult
                </span>
            </baseline-button>
            <baseline-button icon="trash-o" v-bind:disabled="true" class="tlp-button-danger">
                <span v-translate>
                    Delete
                </span>
            </baseline-button>
        </td>
    </tr>
</template>
<script>
import BaselineButton from "./BaselineButton.vue";
export default {
    name: "BaselineTableBodyCellSkeleton",
    components: { BaselineButton }
};
</script>
