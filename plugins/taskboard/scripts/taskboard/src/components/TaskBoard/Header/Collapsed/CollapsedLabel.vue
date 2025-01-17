<template>
    <div
        class="taskboard-header-collapsed-label"
        v-on:pointerenter="pointerEntersCollapsedColumn"
        v-on:pointerleave="pointerLeavesCollapsedColumn"
        v-on:click="expandColumn(column)"
    >
        <cards-in-column-count v-bind:column="column" />
        <span class="taskboard-header-label" data-test="label">{{ column.label }}</span>
    </div>
</template>
<script lang="ts">
import CardsInColumnCount from "../Expanded/CardsInColumnCount.vue";
import { Component, Mixins, Prop } from "vue-property-decorator";
import HoveringStateForCollapsedColumnMixin from "../../Body/Swimlane/Cell/hovering-state-for-collapsed-column-mixin";
import type { ColumnDefinition } from "../../../../type";
import { namespace } from "vuex-class";

const column_store = namespace("column");

@Component({
    components: { CardsInColumnCount },
})
export default class CollapsedLabel extends Mixins(HoveringStateForCollapsedColumnMixin) {
    @column_store.Action
    readonly expandColumn!: (column: ColumnDefinition) => void;

    @Prop({ required: true })
    override readonly column!: ColumnDefinition;
}
</script>
