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
    <word-highlighter
        v-bind:query="keywords"
        v-bind:highlight-class="'tlp-mark-on-dark-background'"
        v-bind:split-by-space="true"
        v-bind:text-to-highlight="text"
        class="switch-to-recent-items-entry-label"
        v-on:matches="emitMatches"
    />
</template>

<script setup lang="ts">
import WordHighlighter from "vue-word-highlighter";
import { useRootStore } from "../../stores/root";
import { computed } from "vue";

defineProps<{ text: string }>();
const emit = defineEmits<{ (e: "matches", words: string[]): void }>();

const root_store = useRootStore();
const keywords = computed((): string => root_store.keywords);

function emitMatches(words: string[]): void {
    emit("matches", words);
}
</script>
