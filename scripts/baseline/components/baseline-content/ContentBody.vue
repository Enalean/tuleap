<template>
    <div class="baseline-content">
        <div v-if="is_loading_failed">
            <div class="tlp-alert-danger">
                <translate>Cannot fetch baseline artifacts</translate>
            </div>
        </div>

        <div
            v-else-if="!is_loading && !are_some_artifacts_available"
            class="baseline-empty-information-message"
            data-test-type="information-message"
        >
            <translate>No artifacts</translate>
        </div>

        <artifacts-list-skeleton v-if="is_loading"/>
        <artifacts-list
            v-else-if="!is_loading_failed"
            v-bind:current_depth="1"
            v-bind:artifacts="artifacts"
            v-bind:baseline_id="baseline_id"
        />
    </div>
</template>

<script>
import { getBaselineArtifacts } from "../../api/rest-querier";
import ArtifactsList from "./ArtifactsList.vue";
import ArtifactsListSkeleton from "./ArtifactsListSkeleton.vue";

export default {
    name: "BaselineContent",

    components: { ArtifactsList, ArtifactsListSkeleton },

    props: {
        baseline_id: { required: true, type: Number }
    },

    data() {
        return {
            artifacts: null,
            is_loading: true,
            is_loading_failed: false
        };
    },

    computed: {
        are_some_artifacts_available() {
            return this.artifacts !== null && this.artifacts.length > 0;
        }
    },

    mounted() {
        this.reset();
        this.fetchArtifacts();
    },

    methods: {
        reset() {
            this.$store.commit("semantics/reset");
        },
        async fetchArtifacts() {
            this.is_loading = true;
            this.is_loading_failed = false;
            try {
                this.artifacts = await getBaselineArtifacts(this.baseline_id);
            } catch (e) {
                this.is_loading_failed = true;
            } finally {
                this.is_loading = false;
            }
        }
    }
};
</script>
