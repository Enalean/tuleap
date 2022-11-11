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
        <tr v-for="index in 3" v-bind:key="index">
            <td><span class="tlp-skeleton-text"></span></td>
            <td><span class="tlp-skeleton-text"></span></td>
            <td>
                <i class="fa-solid fa-user tlp-skeleton-icon" aria-hidden="true"></i>
                <span class="tlp-skeleton-text"></span>
            </td>
            <td><span class="tlp-skeleton-text"></span></td>
            <td><span class="tlp-skeleton-text"></span></td>
            <td v-if="!is_link"><span class="tlp-skeleton-text"></span></td>
            <td v-if="!is_link"></td>
        </tr>
    </tbody>
</template>
<script setup lang="ts">
import type { Item } from "../../type";
import { computed } from "vue";
import { isLink } from "../../helpers/type-check-helper";

const props = defineProps<{ item: Item }>();

const is_link = computed((): boolean => isLink(props.item));
</script>
