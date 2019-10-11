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
    <swimlane-header v-bind:swimlane="swimlane">
        <div class="taskboard-cell-parent-card">
            <parent-card v-bind:card="swimlane.card"/>
            <parent-card-remaining-effort v-bind:card="swimlane.card"/>
        </div>
        <no-mapping-message v-if="should_no_mapping_message_be_displayed" v-bind:card="swimlane.card"/>
    </swimlane-header>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Swimlane } from "../../../../type";
import ParentCard from "./Card/ParentCard.vue";
import NoMappingMessage from "./Header/NoMappingMessage.vue";
import ParentCardRemainingEffort from "./Card/ParentCardRemainingEffort.vue";
import SwimlaneHeader from "./Header/SwimlaneHeader.vue";

@Component({
    components: { NoMappingMessage, ParentCard, ParentCardRemainingEffort, SwimlaneHeader }
})
export default class ParentCell extends Vue {
    @Prop({ required: true })
    readonly swimlane!: Swimlane;

    get should_no_mapping_message_be_displayed(): boolean {
        return !this.swimlane.card.has_children;
    }
}
</script>
