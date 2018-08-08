<template>
    <div class="move-artifact-selectors">
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
            areTrackersLoading: state => state.are_trackers_loading
        }),
        spinner_class() {
            if (this.areTrackersLoading) {
                return "move-artifact-tracker-loader move-artifact-tracker-loader-spinner";
            }
            return "move-artifact-tracker-loader";
        }
    }
};
</script>
