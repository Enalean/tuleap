<template>
    <div class="statistics">
        <statistic v-bind:value="5" v-bind:label="'Epics'" class="baseline-statistic-epics"/>
        <statistic v-bind:value="10" v-bind:label="'User stories'" class="baseline-statistic-user-stories"/>
        <statistic v-bind:value="11" v-bind:label="'Bugs'" class="baseline-statistic-bugs"/>
        <statistic v-bind:value="33" v-bind:label="'Tasks'" class="baseline-statistic-tasks"/>
        <statistic v-bind:value="400" v-bind:label="'Initial effort'" class="baseline-statistic-initial-effort"/>
    </div>
</template>

<script>
import Statistic from "../common/Statistic.vue";

export default {
    name: "BaselineStatistics",
    components: { Statistic }
};
</script>
