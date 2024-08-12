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
            <a class="link" v-bind:href="props.artifact.badge.uri">
                <span v-bind:class="getCrossRefBadgeClass(props.artifact)">{{
                    props.artifact.badge.cross_ref
                }}</span
                >{{ props.artifact.title }}
            </a>
        </td>
        <td>
            <a v-bind:href="'/' + props.artifact.project.uri" class="link">{{
                props.artifact.project.label
            }}</a>
        </td>
        <td>{{ props.artifact.status }}</td>
        <td class="dimmed">
            {{ props.artifact.formatted_last_update_date }}
        </td>
        <td><list-bind-user class="dimmed" v-bind:user="props.artifact.submitted_by" /></td>
        <td>
            <list-bind-user
                class="dimmed"
                v-for="user in props.artifact.assigned_to"
                v-bind:user="user"
                v-bind:key="user.id"
            />
        </td>
    </tr>
</template>

<script setup lang="ts">
import type { Artifact } from "../../type";
import ListBindUser from "./ListBindUser.vue";

const props = defineProps<{ artifact: Artifact }>();

const getCrossRefBadgeClass = (artifact: Artifact): string =>
    `cross-ref-badge tlp-swatch-${artifact.badge.color}`;
</script>

<style scoped lang="scss">
@use "../../../themes/links";
@use "../../../themes/badges";

.link {
    @include links.link;
}

.cross-ref-badge {
    @include badges.badge;
}

.dimmed {
    color: var(--tlp-dimmed-color);
    font-size: 0.875rem;
}
</style>
