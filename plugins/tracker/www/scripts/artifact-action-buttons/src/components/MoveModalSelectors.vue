<template>
    <div v-bind:class="selector_class">
        <project-selector v-if="! this.is_loading_initial"/>
        <div v-bind:class="spinner_class"></div>
        <tracker-selector v-if="! this.is_loading_initial"/>
    </div>
</template>

<script>
import ProjectSelector from "./ProjectSelector.vue";
import TrackerSelector from "./TrackerSelector.vue";
import { mapState } from "vuex";

export default {
    name: "MoveModalSelectors",
    components: {
        ProjectSelector,
        TrackerSelector
    },
    computed: {
        ...mapState(["is_loading_initial", "are_trackers_loading", "has_processed_dry_run"]),
        spinner_class() {
            if (this.are_trackers_loading) {
                return "move-artifact-tracker-loader move-artifact-tracker-loader-spinner";
            }
            return "move-artifact-tracker-loader";
        },
        selector_class() {
            if (this.has_processed_dry_run) {
                return "move-artifact-selectors move-artifact-selectors-preview";
            }

            return "move-artifact-selectors";
        }
    }
};
</script>
