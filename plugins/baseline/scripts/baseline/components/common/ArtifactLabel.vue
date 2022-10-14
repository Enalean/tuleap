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
    <span>
        <artifact-link v-bind:artifact="artifact">
            <span
                class="baseline-artifact-badge tlp-badge-primary tlp-badge-outline tlp-badge-rounded"
            >
                {{ artifact.tracker_name }} #{{ artifact.id }}
            </span>
            <span class="baseline-artifact-title">{{ artifact.title }}</span>
        </artifact-link>
    </span>
</template>
<script setup lang="ts">
import ArtifactLink from "./ArtifactLink.vue";
import type { Artifact } from "../../type";

defineProps<{ artifact: Artifact }>();
</script>
