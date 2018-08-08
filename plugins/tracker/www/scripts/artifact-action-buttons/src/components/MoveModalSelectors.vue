<template>
    <div v-bind:class="selector_class">
        <project-selector v-if="! this.isLoadingInitial"/>
        <div v-bind:class="spinner_class"></div>
        <tracker-selector v-if="! this.isLoadingInitial"/>
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
        ...mapState({
            isLoadingInitial: state => state.is_loading_initial,
            areTrackersLoading: state => state.are_trackers_loading,
            hasProcessedDryRun: state => state.has_processed_dry_run
        }),
        spinner_class() {
            if (this.areTrackersLoading) {
                return "move-artifact-tracker-loader move-artifact-tracker-loader-spinner";
            }
            return "move-artifact-tracker-loader";
        },
        selector_class() {
            if (this.hasProcessedDryRun) {
                return "move-artifact-selectors move-artifact-selectors-preview";
            }

            return "move-artifact-selectors";
        }
    }
};
</script>
