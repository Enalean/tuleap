<!--
  - Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
    <h1>
        {{ title }} <a v-bind:href="artifact_url">#{{ artifact_id }}</a>
    </h1>
</template>
<script setup lang="ts">
import { onMounted } from "vue";
import useScrollToAnchor from "@/composables/useScrollToAnchor";

const props = defineProps<{
    artifact_id: number;
    title: string;
}>();
const artifact_url = `/plugins/tracker/?aid=${props.artifact_id}`;

const { scrollToAnchor } = useScrollToAnchor();
onMounted(() => {
    const hash = window.location.hash.slice(1);
    if (hash) {
        scrollToAnchor(hash);
    }
});
</script>
<style lang="scss" scoped>
h1 {
    margin: 0;
    color: var(--tlp-dark-color);
    font-size: 1.5rem;
    font-weight: 600;
    line-height: 3rem;
}

a {
    margin: 0 0 0 var(--tlp-medium-spacing);
    font-size: 1rem;
    font-weight: 400;
}
</style>
