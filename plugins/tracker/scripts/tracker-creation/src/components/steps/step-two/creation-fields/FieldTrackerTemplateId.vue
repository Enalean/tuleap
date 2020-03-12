<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <input type="hidden" name="tracker-template-id" v-bind:value="tracker_id" />
</template>
<script lang="ts">
import Vue from "vue";
import { State, Getter } from "vuex-class";
import { Component } from "vue-property-decorator";
import { Tracker } from "../../../../store/type";

@Component
export default class FieldTrackerTemplateId extends Vue {
    @State
    readonly selected_tracker_template!: Tracker;

    @State
    readonly selected_project_tracker_template!: Tracker;

    @Getter
    readonly is_a_duplication!: boolean;

    get tracker_id(): string {
        if (this.is_a_duplication) {
            return this.selected_tracker_template.id;
        }

        return this.selected_project_tracker_template.id;
    }
}
</script>
