<template>
    <div class="baseline-content">
        <div
            v-if="first_level_artifacts.length === 0"
            class="baseline-empty-information-message"
            data-test-type="information-message"
        >
            <translate>No artifacts</translate>
        </div>

        <artifacts-list
            v-bind:artifacts="first_level_artifacts"
        />
    </div>
</template>

<script>
import ArtifactsList from "./ArtifactsList.vue";

export default {
    name: "ContentBody",

    components: { ArtifactsList },

    props: {
        first_level_artifacts: { required: true, type: Array }
    },

    mounted() {
        this.reset();
    },

    methods: {
        reset() {
            this.$store.commit("semantics/reset");
        }
    }
};
</script>
