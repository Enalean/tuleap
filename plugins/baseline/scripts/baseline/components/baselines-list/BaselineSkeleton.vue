<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
            <action-button icon="eye" v-bind:disabled="true" class="tlp-table-cell-actions-button">
                <span v-translate>Consult</span>
            </action-button>
            <action-button
                icon="trash-o"
                v-bind:disabled="true"
                class="tlp-button-danger tlp-table-cell-actions-button"
                v-if="is_admin"
            >
                <span v-translate>Delete</span>
            </action-button>
        </td>
    </tr>
</template>
<script setup lang="ts">
import ActionButton from "../common/ActionButton.vue";
import { inject } from "vue";

const is_admin = inject("is_admin");
</script>
