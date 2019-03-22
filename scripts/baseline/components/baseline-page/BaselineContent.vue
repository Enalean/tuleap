<template>
    <div>
        <div v-if="is_loading_failed">
            <div class="tlp-alert-danger">
                <translate>Cannot fetch baseline artifacts</translate>
            </div>
        </div>

        <div
            v-else-if="!is_loading && !are_some_artifacts_available"
            class="baseline-content-artifacts-information-message"
            data-test-type="information-message"
        >
            <translate>No artifacts</translate>
        </div>

        <baseline-artifacts-skeleton v-if="is_loading"/>
        <baseline-artifacts v-else-if="!is_loading_failed"
                            v-bind:artifacts="artifacts"
                            v-bind:baseline_id="baseline_id"
        />
    </div>
</template>

<script>
import { getBaselineArtifacts } from "../../api/rest-querier";
import BaselineArtifacts from "./BaselineArtifacts.vue";
import BaselineArtifactsSkeleton from "./BaselineArtifactsSkeleton.vue";

export default {
    name: "BaselineContent",

    components: { BaselineArtifacts, BaselineArtifactsSkeleton },

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
        this.resetSemanticFields();
        this.fetchArtifacts();
    },

    methods: {
        resetSemanticFields() {
            this.$store.commit("resetSemanticFields");
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
