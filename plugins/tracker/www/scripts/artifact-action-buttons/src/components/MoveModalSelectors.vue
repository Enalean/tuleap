<template>
    <div class="move-artifact-selectors">
        <project-selector v-if="should_display_dropdown"/>
        <div v-bind:class="spinner_class"></div>
        <tracker-selector v-if="should_display_dropdown"/>
    </div>
</template>

<script>
import ProjectSelector from "./ProjectSelector.vue";
import TrackerSelector from "./TrackerSelector.vue";
import { mapGetters, mapState } from "vuex";

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
        ...mapGetters(["hasError"]),
        should_display_dropdown() {
            return !this.isLoadingInitial && !this.hasError;
        },
        spinner_class() {
            if (this.areTrackersLoading) {
                return "move-artifact-tracker-loader move-artifact-tracker-loader-spinner";
            }
            return "move-artifact-tracker-loader";
        }
    }
};
</script>
