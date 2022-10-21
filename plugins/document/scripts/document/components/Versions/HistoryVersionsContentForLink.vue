<!--
  - Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
    <tbody>
        <tr v-for="version in versions" v-bind:key="version.id">
            <td class="tlp-table-cell-numeric">
                <a v-bind:href="version.link_href">{{ version.number }}</a>
            </td>
            <td>
                <document-relative-date v-bind:date="version.date" />
            </td>
            <td>
                <user-badge v-bind:user="version.author" />
            </td>
            <td>{{ version.name }}</td>
            <td>{{ version.changelog }}</td>
        </tr>
    </tbody>
</template>

<script setup lang="ts">
import UserBadge from "../User/UserBadge.vue";
import DocumentRelativeDate from "../Date/DocumentRelativeDate.vue";
import type { LinkVersion } from "../../type";

defineProps<{ versions: readonly LinkVersion[] }>();
</script>
