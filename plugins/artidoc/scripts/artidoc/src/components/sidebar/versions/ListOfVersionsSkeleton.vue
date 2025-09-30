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
        <version-with-timeline v-for="n in 100" v-bind:key="n">
            <version-disc v-bind:is_version_with_title="false" />
            <version-entry-skeleton />
        </version-with-timeline>
    </list-of-versions>
</template>

<script setup lang="ts">
import ListOfVersions from "./ListOfVersions.vue";
import VersionDisc from "./VersionDisc.vue";
import VersionEntrySkeleton from "./VersionEntrySkeleton.vue";
import VersionWithTimeline from "@/components/sidebar/versions/VersionWithTimeline.vue";
</script>
