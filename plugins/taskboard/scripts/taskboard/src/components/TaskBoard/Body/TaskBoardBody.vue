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
import { ReorderCardsPayload } from "../../../store/swimlane/type";
import CollapsedSwimlane from "./Swimlane/CollapsedSwimlane.vue";
import CardWithChildren from "./Swimlane/CardWithChildren.vue";
import SwimlaneSkeleton from "./Swimlane/Skeleton/SwimlaneSkeleton.vue";
import SoloSwimlane from "./Swimlane/SoloSwimlane.vue";
import InvalidMappingSwimlane from "./Swimlane/InvalidMappingSwimlane.vue";
import {
    hasCardBeenDroppedInTheSameCell,
    getCardFromSwimlane
} from "../../../helpers/html-to-item";
import { getCardPosition } from "../../../helpers/cards-reordering";
import { getColumnOfCard } from "../../../helpers/list-value-to-column-mapper";

const swimlane = namespace("swimlane");

const canMove = (element: Element, handle: Element): boolean => {
    return (
        !element.classList.contains("taskboard-card-collapsed") &&
        !handle.classList.contains("taskboard-item-no-drag") &&
        element.classList.contains("taskboard-card")
    );
};

const isContainer = (element: Element): boolean => {
    return (
        element.classList.contains("taskboard-cell") &&
        !element.classList.contains("taskboard-cell-swimlane-header") &&
        !element.classList.contains("taskboard-swimlane-collapsed-cell-placeholder") &&
        !element.classList.contains("taskboard-card-parent")
    );
};

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

    @State
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
    reorderCardsInCell!: (payload: ReorderCardsPayload) => void;

    drake!: Drake;

    created(): void {
        this.loadSwimlanes();
    }

    beforeDestroy(): void {
        if (this.drake) {
            this.drake.destroy();
        }
    }

    mounted(): void {
        this.drake = dragula({
            isContainer(element?: Element): boolean {
                if (!element) {
                    return false;
                }
                return isContainer(element);
            },
            moves(element?: Element, container?: Element, handle?: Element): boolean {
                if (!element || !handle) {
                    return false;
                }

                return canMove(element, handle);
            },
            accepts(element, target, source): boolean {
                if (
                    !target ||
                    !source ||
                    !(target instanceof HTMLElement) ||
                    !(source instanceof HTMLElement)
                ) {
                    return false;
                }

                return hasCardBeenDroppedInTheSameCell(target, source);
            }
        });

        this.drake.on(
            "drop",
            (
                dropped_card: HTMLElement,
                target_cell: HTMLElement,
                source_cell: HTMLElement,
                sibling_card?: HTMLElement
            ) => {
                if (!hasCardBeenDroppedInTheSameCell(target_cell, source_cell)) {
                    return;
                }

                const { swimlane, column } = this.column_and_swimlane_of_cell(target_cell);

                if (!swimlane || !column) {
                    return;
                }

                const card = getCardFromSwimlane(swimlane, dropped_card);

                if (!card) {
                    return;
                }

                const sibling = getCardFromSwimlane(swimlane, sibling_card);
                const position = getCardPosition(
                    card,
                    sibling,
                    this.cards_in_cell(swimlane, column)
                );

                const payload: ReorderCardsPayload = {
                    swimlane,
                    column,
                    position
                };

                this.reorderCardsInCell(payload);
            }
        );
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
}
</script>
