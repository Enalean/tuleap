<template>
    <ol class="baseline-content-artifact-ol">
        <li
            v-for="artifact in artifacts" v-bind:key="artifact.id"
            class="baseline-content-artifact-li"
        >
            <artifact
                v-bind:baseline_id="baseline_id"
                v-bind:artifact="artifact"
                v-bind:current_depth="current_depth"
            />
        </li>
    </ol>
</template>
<script>
import Artifact from "./Artifact.vue";

export default {
    name: "Artifacts",

    components: { Artifact },

    props: {
        artifacts: { required: false, type: Array },
        baseline_id: { required: true, type: Number },
        current_depth: { required: true, type: Number }
    },

    beforeCreate() {
        this.$options.components.Artifact = Artifact;
    }
};
</script>
