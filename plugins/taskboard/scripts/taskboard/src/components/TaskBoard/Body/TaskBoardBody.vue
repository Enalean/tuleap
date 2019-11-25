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
            <template v-if="swimlane.card.is_open || are_closed_items_displayed">
                <collapsed-swimlane v-bind:key="swimlane.card.id" v-bind:swimlane="swimlane" v-if="swimlane.card.is_collapsed"/>
                <card-with-children v-bind:key="swimlane.card.id" v-bind:swimlane="swimlane" v-else-if="swimlane.card.has_children"/>
                <invalid-mapping-swimlane v-bind:key="swimlane.card.id" v-bind:swimlane="swimlane" v-else-if="hasInvalidMapping(swimlane)"/>
                <solo-swimlane
                    v-bind:key="swimlane.card.id"
                    v-bind:swimlane="swimlane"
                    v-bind:column="getColumnOfSoloCard(swimlane)"
                    v-else
                />
            </template>
        </template>
        <swimlane-skeleton v-if="is_loading_swimlanes"/>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { namespace, State } from "vuex-class";
import dragula, { Drake } from "dragula";
import { Swimlane, ColumnDefinition, Card } from "../../../type";
import CollapsedSwimlane from "./Swimlane/CollapsedSwimlane.vue";
import CardWithChildren from "./Swimlane/CardWithChildren.vue";
import SwimlaneSkeleton from "./Swimlane/Skeleton/SwimlaneSkeleton.vue";
import SoloSwimlane from "./Swimlane/SoloSwimlane.vue";
import InvalidMappingSwimlane from "./Swimlane/InvalidMappingSwimlane.vue";
import { getColumnOfCard } from "../../../helpers/list-value-to-column-mapper";
import { isContainer, canMove, invalid, checkCellAcceptsDrop } from "../../../helpers/drag-drop";
import { HandleDropPayload } from "../../../store/swimlane/type";

const column = namespace("column");
const swimlane = namespace("swimlane");

@Component({
    components: {
        InvalidMappingSwimlane,
        SoloSwimlane,
        SwimlaneSkeleton,
        CardWithChildren,
        CollapsedSwimlane
    }
})
export default class TaskBoardBody extends Vue {
    @State
    readonly are_closed_items_displayed!: boolean;

    @column.State
    readonly columns!: Array<ColumnDefinition>;

    @swimlane.State
    readonly swimlanes!: Array<Swimlane>;

    @swimlane.State
    readonly is_loading_swimlanes!: boolean;

    @swimlane.Getter
    readonly cards_in_cell!: (
        current_swimlane: Swimlane,
        current_column: ColumnDefinition
    ) => Card[];

    @swimlane.Getter
    readonly column_and_swimlane_of_cell!: (
        cell: HTMLElement
    ) => {
        swimlane?: Swimlane;
        column?: ColumnDefinition;
    };

    @swimlane.Action
    loadSwimlanes!: () => void;

    @swimlane.Action
    handleDrop!: (payload: HandleDropPayload) => void;

    @swimlane.Mutation
    readonly removeHighlightOnLastHoveredDropZone!: () => void;

    drake!: Drake | undefined;

    created(): void {
        this.loadSwimlanes();
    }

    beforeDestroy(): void {
        if (this.drake) {
            this.drake.destroy();
        }
        document.removeEventListener("keyup", this.cancelDragOnEscape);
    }

    mounted(): void {
        this.drake = dragula({
            isContainer,
            moves: canMove,
            invalid,
            revertOnSpill: true,
            accepts: (
                dropped_card?: Element,
                target_cell?: Element,
                source_cell?: Element
            ): boolean =>
                checkCellAcceptsDrop(this.$store, { dropped_card, target_cell, source_cell })
        });

        this.drake.on(
            "drop",
            (
                dropped_card: HTMLElement,
                target_cell: HTMLElement,
                source_cell: HTMLElement,
                sibling_card?: HTMLElement
            ) => this.handleDrop({ dropped_card, target_cell, source_cell, sibling_card })
        );

        this.drake.on("cancel", this.removeHighlightOnLastHoveredDropZone);

        document.addEventListener("keyup", this.cancelDragOnEscape);
    }

    getColumnOfSoloCard(swimlane: Swimlane): ColumnDefinition {
        const column = getColumnOfCard(this.columns, swimlane.card);
        if (column === undefined) {
            throw new Error("Solo card must have a mapping");
        }
        return column;
    }

    hasInvalidMapping(swimlane: Swimlane): boolean {
        return getColumnOfCard(this.columns, swimlane.card) === undefined;
    }

    cancelDragOnEscape(event: KeyboardEvent): void {
        if (event.key === "Escape" && this.drake) {
            this.drake.cancel(true);
        }
    }
}
</script>
