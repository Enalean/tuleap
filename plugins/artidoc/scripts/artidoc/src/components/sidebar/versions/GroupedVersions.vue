<!--
  - Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
    <version-with-timeline>
        <version-disc v-bind:is_version_with_title="true" />
        <version-entry v-bind:version="group.parent" v-bind:toggle_state="toggle_state" />
    </version-with-timeline>
    <template v-if="is_open">
        <version-with-timeline v-for="version in group.versions" v-bind:key="version.id">
            <version-disc v-bind:is_version_with_title="false" />
            <version-entry v-bind:version="version" />
        </version-with-timeline>
    </template>
</template>

<script setup lang="ts">
import { ref } from "vue";
import VersionEntry from "./VersionEntry.vue";
import VersionDisc from "./VersionDisc.vue";
import VersionWithTimeline from "./VersionWithTimeline.vue";
import type { VersionsUnderVersion } from "./group-versions-by-named-version";
import type { ToggleState } from "./toggle-state";

defineProps<{ group: VersionsUnderVersion }>();

const is_open = ref(false);

const toggle_state: ToggleState = {
    is_open,
    toggle(): void {
        is_open.value = !is_open.value;
    },
};
</script>
