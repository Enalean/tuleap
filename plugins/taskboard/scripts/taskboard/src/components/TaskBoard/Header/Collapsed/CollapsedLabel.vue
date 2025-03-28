<template>
    <div class="taskboard-header-collapsed-label">
        <cards-in-column-count v-bind:column="column" />
        <span class="taskboard-header-label">{{ column.label }}</span>
    </div>
</template>
<script setup lang="ts">
import CardsInColumnCount from "../Expanded/CardsInColumnCount.vue";
import type { ColumnDefinition } from "../../../../type";

const { column } = defineProps<{ column: ColumnDefinition }>();
</script>
