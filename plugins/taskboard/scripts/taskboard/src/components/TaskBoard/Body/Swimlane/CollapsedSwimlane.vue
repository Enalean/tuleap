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
        class="taskboard-swimlane taskboard-swimlane-collapsed"
        data-navigation="swimlane"
        tabindex="0"
    >
        <swimlane-header v-bind:swimlane="swimlane">
            <template v-slot:toggle>
                <button
                    class="taskboard-swimlane-toggle"
                    v-bind:class="additional_classnames"
                    type="button"
                    v-bind:title="title"
                    v-on:click="expandSwimlane(swimlane)"
                    data-test="swimlane-toggle"
                >
                    <i class="fa fa-plus-square" aria-hidden="true"></i>
                </button>
            </template>
            <template v-slot:default>
                <div
                    class="taskboard-card taskboard-card-collapsed"
                    tabindex="0"
                    data-navigation="card"
                    data-shortcut="parent-card"
                    v-bind:class="additional_card_classnames"
                >
                    <div class="taskboard-card-content">
                        <card-xref-label
                            v-bind:card="swimlane.card"
                            v-bind:label="swimlane.card.label"
                            class="taskboard-card-xref-label-collapsed"
                        />
                    </div>
                </div>
            </template>
        </swimlane-header>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { Swimlane, ColumnDefinition } from "../../../../type";
import { namespace } from "vuex-class";
import CardXrefLabel from "./Card/CardXrefLabel.vue";
import SwimlaneHeader from "./Header/SwimlaneHeader.vue";

const column_store = namespace("column");
const swimlane_store = namespace("swimlane");

@Component({
    components: { SwimlaneHeader, CardXrefLabel },
})
export default class CollapsedSwimlane extends Vue {
    @Prop({ required: true })
    readonly swimlane!: Swimlane;

    @swimlane_store.Action
    readonly expandSwimlane!: (swimlane: Swimlane) => void;

    @column_store.State
    readonly columns!: Array<ColumnDefinition>;

    get additional_classnames(): string {
        return `tlp-swatch-${this.swimlane.card.color}`;
    }

    get additional_card_classnames(): string {
        return `taskboard-card-${this.swimlane.card.color}`;
    }

    get title(): string {
        return this.$gettext("Expand");
    }
}
</script>
