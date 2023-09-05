<template>
    <div
        class="taskboard-header-collapsed-label"
        v-on:pointerenter="pointerEntersCollapsedColumn"
        v-on:pointerleave="pointerLeavesCollapsedColumn"
        v-on:click="expandCollapsedColumn"
    >
        <cards-in-column-count v-bind:column="column" />
        <span class="taskboard-header-label" data-test="label">{{ column.label }}</span>
    </div>
</template>
<script lang="ts">
import CardsInColumnCount from "../Expanded/CardsInColumnCount.vue";
import { Component, Mixins } from "vue-property-decorator";
import ExpandCollapsedColumnMixin from "../../Body/Swimlane/Cell/expand-collapsed-column-mixin";
import HoveringStateForCollapsedColumnMixin from "../../Body/Swimlane/Cell/hovering-state-for-collapsed-column-mixin";

@Component({
    components: { CardsInColumnCount },
})
export default class CollapsedLabel extends Mixins(
    ExpandCollapsedColumnMixin,
    HoveringStateForCollapsedColumnMixin,
) {}
</script>
