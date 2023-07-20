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
    <div
        class="tlp-alert-danger cross-tracker-report-error"
        v-if="has_invalid_trackers && is_user_admin"
    >
        <translate>The initial query contains trackers from inactive projects:</translate>
        <ul>
            <li v-for="tracker in invalid_trackers" v-bind:key="tracker.id">
                {{ tracker.label }} ({{ tracker.project.label }})
            </li>
        </ul>
        <translate>If you update the query these trackers will be removed from it.</translate>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import Component from "vue-class-component";
import { State, Getter } from "vuex-class";
import type { InvalidTracker } from "../type";

@Component
export default class ErrorInactiveProjectMessage extends Vue {
    @State
    readonly invalid_trackers!: InvalidTracker[];

    @State
    readonly is_user_admin!: boolean;

    @Getter
    readonly has_invalid_trackers!: boolean;
}
</script>
