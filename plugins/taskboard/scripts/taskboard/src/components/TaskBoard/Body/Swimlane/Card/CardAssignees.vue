<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <div class="taskboard-card-assignees" v-bind:class="classes">
        <i
            class="fa"
            v-bind:class="user_edit_classes"
            v-if="is_user_edit_displayed"
            data-test="icon"
        ></i>
        <user-avatar
            v-for="assignee in card.assignees"
            class="taskboard-card-assignees-avatars"
            v-bind:user="assignee"
            v-bind:key="assignee.id"
        />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Card, Tracker } from "../../../../../type";
import UserAvatar from "./UserAvatar.vue";
@Component({
    components: { UserAvatar }
})
export default class CardAssignees extends Vue {
    @Prop({ required: true })
    readonly card!: Card;

    @Prop({ required: true })
    readonly tracker!: Tracker;

    get classes(): string[] {
        if (!this.card.is_in_edit_mode) {
            return [];
        }

        const classes = ["taskboard-card-assignees-edit-mode"];

        if (this.is_updatable) {
            classes.push("taskboard-card-assignees-editable");
        }

        return classes;
    }

    get user_edit_classes(): string[] {
        if (this.card.assignees.length >= 1) {
            return ["fa-tlp-user-pencil", "taskboard-card-assignees-edit-icon"];
        }

        return ["fa-user-plus", "taskboard-card-assignees-add-icon"];
    }

    get is_user_edit_displayed(): boolean {
        return this.card.is_in_edit_mode && this.is_updatable;
    }

    get is_updatable(): boolean {
        return this.tracker.assigned_to_field !== null;
    }
}
</script>
