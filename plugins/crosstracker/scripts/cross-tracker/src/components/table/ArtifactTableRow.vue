<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
  -->

<template>
    <tr>
        <td data-test="cross-tracker-results-artifact">
            <a class="direct-link-to-artifact" v-bind:href="props.artifact.badge.uri">
                <span class="cross-ref-badge link-to-tracker-badge" v-bind:class="badge_color">{{
                    props.artifact.badge.cross_ref
                }}</span
                >{{ props.artifact.title }}
            </a>
        </td>
        <td>
            <a v-bind:href="'/' + props.artifact.project.uri" class="cross-tracker-project">{{
                props.artifact.project.label
            }}</a>
        </td>
        <td>{{ props.artifact.status }}</td>
        <td class="cross-tracker-last-update-date">
            {{ props.artifact.formatted_last_update_date }}
        </td>
        <td><list-bind-user v-bind:user="props.artifact.submitted_by" /></td>
        <td>
            <list-bind-user
                v-for="user in props.artifact.assigned_to"
                v-bind:user="user"
                v-bind:key="user.id"
            />
        </td>
    </tr>
</template>
<script setup lang="ts">
import { computed } from "vue";
import type { Artifact } from "../../type";
import ListBindUser from "../ListBindUser.vue";

const props = defineProps<{ artifact: Artifact }>();
const badge_color = computed(() => `tlp-swatch-${props.artifact.badge.color}`);
</script>
