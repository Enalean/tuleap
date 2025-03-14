<template>
    <div
        class="taskboard-header-collapsed-label"
        v-on:pointerenter="pointerEntersColumn(column)"
        v-on:pointerleave="pointerLeavesColumn({ column, card_being_dragged })"
        v-on:click="expandColumn(column)"
    >
        <cards-in-column-count v-bind:column="column" />
        <span class="taskboard-header-label" data-test="label">{{ column.label }}</span>
    </div>
</template>
<script lang="ts">
import CardsInColumnCount from "../Expanded/CardsInColumnCount.vue";
import { Component, Prop } from "vue-property-decorator";
import type { ColumnDefinition } from "../../../../type";
import { namespace, State } from "vuex-class";
import type { DraggedCard } from "../../../../store/type";
import type { PointerLeavesColumnPayload } from "../../../../store/column/type";
import Vue from "vue";

const column_store = namespace("column");

@Component({
    components: { CardsInColumnCount },
})
export default class CollapsedLabel extends Vue {
    @column_store.Action
    readonly expandColumn!: (column: ColumnDefinition) => void;

    @State
    readonly card_being_dragged!: DraggedCard | null;

    @column_store.Mutation
    readonly pointerEntersColumn!: (column: ColumnDefinition) => void;

    @column_store.Mutation
    readonly pointerLeavesColumn!: (payload: PointerLeavesColumnPayload) => void;

    @Prop({ required: true })
    readonly column!: ColumnDefinition;
}
</script>
