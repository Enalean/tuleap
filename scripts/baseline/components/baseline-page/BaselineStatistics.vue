<template>
    <div class="baseline-statistics">
        <baseline-statistic v-bind:value="5" v-bind:label="'Epics'" class="baseline-statistics-epics"/>
        <baseline-statistic v-bind:value="10" v-bind:label="'User stories'" class="baseline-statistics-user-stories"/>
        <baseline-statistic v-bind:value="11" v-bind:label="'Bugs'" class="baseline-statistics-bugs"/>
        <baseline-statistic v-bind:value="33" v-bind:label="'Tasks'" class="baseline-statistics-tasks"/>
        <baseline-statistic v-bind:value="400" v-bind:label="'Initial effort'" class="baseline-statistics-initial-effort"/>
    </div>
</template>

<script>
import BaselineStatistic from "./BaselineStatistic.vue";

export default {
    name: "BaselineStatistics",
    components: { BaselineStatistic }
};
</script>
