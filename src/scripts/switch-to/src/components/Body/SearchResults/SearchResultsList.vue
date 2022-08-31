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
    <ul>
        <li v-for="(item, key) of results" v-bind:key="key">
            <highlight-matching-text>{{ item.title }}</highlight-matching-text>
        </li>
    </ul>
</template>

<script setup lang="ts">
import { useFullTextStore } from "../../../stores/fulltext";
import { computed } from "vue";
import HighlightMatchingText from "../HighlightMatchingText.vue";
import type { ItemEntry } from "../../../type";

const fulltext_store = useFullTextStore();
const results = computed((): ItemEntry[] => fulltext_store.fulltext_search_results);
</script>
