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
    <list-of-versions>
        <version-with-timeline v-for="version in versions" v-bind:key="version.id">
            <version-disc v-bind:is_version_with_title="version.title.isValue()" />
            <version-entry v-bind:version="version" />
        </version-with-timeline>
    </list-of-versions>
</template>

<script setup lang="ts">
import type { Version } from "./fake-list-of-versions";
import VersionEntry from "./VersionEntry.vue";
import ListOfVersions from "@/components/sidebar/versions/ListOfVersions.vue";
import VersionDisc from "@/components/sidebar/versions/VersionDisc.vue";
import VersionWithTimeline from "@/components/sidebar/versions/VersionWithTimeline.vue";

defineProps<{ versions: ReadonlyArray<Version> }>();
</script>
