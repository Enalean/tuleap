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
    <div class="taskboard-body">
        <template v-for="swimlane of swimlanes">
            <collapsed-swimlane v-bind:key="swimlane.card.id" v-bind:swimlane="swimlane" v-if="swimlane.is_collapsed"/>
            <card-with-children v-bind:key="swimlane.card.id" v-bind:swimlane="swimlane" v-else-if="swimlane.card.has_children"/>
            <solo-card v-bind:key="swimlane.card.id" v-bind:swimlane="swimlane" v-else/>
        </template>
        <swimlane-skeleton v-if="is_loading_swimlanes"/>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { namespace } from "vuex-class";
import { Swimlane } from "../../../type";
import CollapsedSwimlane from "./Swimlane/CollapsedSwimlane.vue";
import CardWithChildren from "./Swimlane/CardWithChildren.vue";
import SoloCard from "./Swimlane/SoloCard.vue";
import SwimlaneSkeleton from "./Swimlane/Skeleton/SwimlaneSkeleton.vue";

const swimlane = namespace("swimlane");

@Component({
    components: { SwimlaneSkeleton, SoloCard, CardWithChildren, CollapsedSwimlane }
})
export default class TaskBoardBody extends Vue {
    @swimlane.State
    readonly swimlanes!: Array<Swimlane>;

    @swimlane.State
    readonly is_loading_swimlanes!: boolean;

    @swimlane.Action
    loadSwimlanes!: () => void;

    created(): void {
        this.loadSwimlanes();
    }
}
</script>
