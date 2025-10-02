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
        <template v-for="group in grouped_versions" v-bind:key="group.id">
            <template v-if="isOrphan(group)">
                <version-with-timeline v-for="version in group.versions" v-bind:key="version.id">
                    <version-disc v-bind:is_version_with_title="version.title.isValue()" />
                    <version-entry v-bind:version="version" />
                </version-with-timeline>
            </template>
            <grouped-versions v-bind:group="group" v-if="isVersionsUnderVersion(group)" />
        </template>
    </list-of-versions>
</template>

<script setup lang="ts">
import VersionEntry from "./VersionEntry.vue";
import ListOfVersions from "@/components/sidebar/versions/ListOfVersions.vue";
import VersionDisc from "@/components/sidebar/versions/VersionDisc.vue";
import VersionWithTimeline from "@/components/sidebar/versions/VersionWithTimeline.vue";
import type { GroupedVersion } from "@/components/sidebar/versions/group-versions-by-named-version";
import {
    isOrphan,
    isVersionsUnderVersion,
} from "@/components/sidebar/versions/group-versions-by-named-version";
import GroupedVersions from "@/components/sidebar/versions/GroupedVersions.vue";

defineProps<{ grouped_versions: ReadonlyArray<GroupedVersion> }>();
</script>
