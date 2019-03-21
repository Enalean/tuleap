<template>
    <div class="statistics">
        <statistic v-bind:value="5" v-bind:label="epics_label" class="baseline-statistic-epics"/>
        <statistic v-bind:value="10" v-bind:label="user_stories_label" class="baseline-statistic-user-stories"/>
        <statistic v-bind:value="11" v-bind:label="bugs_label" class="baseline-statistic-bugs"/>
        <statistic v-bind:value="33" v-bind:label="tasks_label" class="baseline-statistic-tasks"/>
        <statistic v-bind:value="400" v-bind:label="initial_effort_label" class="baseline-statistic-initial-effort"/>
    </div>
</template>

<script>
import Statistic from "../common/Statistic.vue";

export default {
    name: "BaselineStatistics",
    components: { Statistic },
    computed: {
        epics_label() {
            return this.$gettext("Epics");
        },
        user_stories_label() {
            return this.$gettext("User stories");
        },
        bugs_label() {
            return this.$gettext("Bugs");
        },
        tasks_label() {
            return this.$gettext("Tasks");
        },
        initial_effort_label() {
            return this.$gettext("Initial effort");
        }
    }
};
</script>
