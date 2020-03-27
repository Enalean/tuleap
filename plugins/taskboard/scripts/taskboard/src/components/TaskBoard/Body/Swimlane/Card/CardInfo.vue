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
    <div class="taskboard-card-info">
        <slot name="initial_effort" v-if="!card.is_in_edit_mode" />
        <card-assignees v-bind:card="card" v-bind:tracker="tracker" v-model="new_assignees" />
    </div>
</template>

<script lang="ts">
import CardAssignees from "./CardAssignees.vue";
import { Component, Prop } from "vue-property-decorator";
import Vue from "vue";
import { Card, Tracker, User } from "../../../../../type";

@Component({
    components: {
        CardAssignees,
    },
})
export default class CardInfo extends Vue {
    @Prop({ required: true })
    readonly value!: User[];

    @Prop({ required: true })
    readonly card!: Card;

    @Prop({ required: true })
    readonly tracker!: Tracker;

    get new_assignees(): User[] {
        return this.value;
    }

    set new_assignees(value) {
        this.$emit("input", value);
    }
}
</script>
