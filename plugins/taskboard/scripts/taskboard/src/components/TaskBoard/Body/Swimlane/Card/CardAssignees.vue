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
    <div
        class="taskboard-card-assignees"
        v-bind:class="classes"
        v-bind:role="role"
        v-bind:tabindex="tabindex"
        v-bind:aria-label="edit_assignees_label"
        v-bind:title="edit_assignees_label"
        v-on:click="editAssignees"
        v-on:keyup.enter="editAssignees"
    >
        <people-picker
            v-bind:is_multiple="is_multiple"
            v-bind:users="users"
            v-model="new_assignees_ids"
            v-if="is_in_edit_mode"
        />
        <template v-else>
            <i
                class="fa"
                v-bind:class="user_edit_classes"
                v-if="is_user_edit_displayed"
                aria-hidden="true"
                data-test="icon"
            ></i>
            <user-avatar
                v-for="assignee in card.assignees"
                class="taskboard-card-assignees-avatars"
                v-bind:user="assignee"
                v-bind:key="assignee.id"
            />
        </template>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { namespace } from "vuex-class";
import type { Card, Tracker, User } from "../../../../../type";
import UserAvatar from "./UserAvatar.vue";
import PeoplePicker from "./Editor/Assignees/PeoplePicker.vue";
import type { UserForPeoplePicker } from "../../../../../store/swimlane/card/type";

const swimlane = namespace("swimlane");

@Component({
    components: { PeoplePicker, UserAvatar },
})
export default class CardAssignees extends Vue {
    @Prop({ required: true })
    readonly value!: User[];

    @Prop({ required: true })
    readonly card!: Card;

    @Prop({ required: true })
    readonly tracker!: Tracker;

    @swimlane.Action
    loadPossibleAssignees!: (tracker: Tracker) => void;

    @swimlane.Getter
    readonly assignable_users!: (tracker: Tracker) => UserForPeoplePicker[];

    is_in_edit_mode = false;
    private possible_users: UserForPeoplePicker[] = [];
    private is_loading_users = false;

    mounted(): void {
        this.$watch(
            () => this.card.is_in_edit_mode,
            function (is_in_edit_mode: boolean) {
                if (!is_in_edit_mode) {
                    this.is_in_edit_mode = false;
                }
            },
        );
    }

    get classes(): string[] {
        if (!this.card.is_in_edit_mode) {
            return [];
        }

        const classes = ["taskboard-card-edit-mode-assignees"];

        if (this.is_in_edit_mode) {
            classes.push("taskboard-card-assignees-edit-mode");
        } else if (this.is_updatable) {
            classes.push("taskboard-card-assignees-editable");
        }

        return classes;
    }

    get user_edit_classes(): string[] {
        if (this.is_loading_users) {
            return ["fa-circle-o-notch", "fa-spin", "taskboard-card-assignees-loading-icon"];
        }

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

    get is_multiple(): boolean {
        return Boolean(this.tracker.assigned_to_field?.is_multiple);
    }

    get edit_assignees_label(): string {
        if (!this.is_user_edit_displayed) {
            return "";
        }

        const number = this.is_multiple ? 2 : 1;

        return this.$ngettext("Edit assignee", "Edit assignees", number);
    }

    get role(): string {
        return this.is_user_edit_displayed ? "button" : "";
    }

    get tabindex(): number {
        return this.is_user_edit_displayed ? 0 : -1;
    }

    async editAssignees(): Promise<void> {
        if (!this.card.is_in_edit_mode || this.is_in_edit_mode) {
            return;
        }

        await this.loadUsers();
        this.is_in_edit_mode = true;
    }

    async loadUsers(): Promise<void> {
        this.is_loading_users = true;

        await this.loadPossibleAssignees(this.tracker);
        this.possible_users = this.assignable_users(this.tracker);

        this.is_loading_users = false;
    }

    get users(): UserForPeoplePicker[] {
        return this.possible_users.map((user): UserForPeoplePicker => {
            const selected = this.card.assignees.some(
                (selected_user) => selected_user.id === user.id,
            );

            return { ...user, selected };
        });
    }

    get new_assignees_ids(): number[] {
        return this.value.map((user) => user.id);
    }

    set new_assignees_ids(value: number[]) {
        this.$emit(
            "input",
            this.users.filter((user) => value.some((id) => id === user.id)),
        );
    }
}
</script>
