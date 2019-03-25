<template>
    <ol class="baseline-content-artifact-ol">
        <li
            v-for="artifact in artifacts" v-bind:key="artifact.id"
            class="baseline-content-artifact-li"
        >
            <baseline-artifact
                v-bind:baseline_id="baseline_id"
                v-bind:artifact="artifact"
                v-bind:current_depth="current_depth"
            />
        </li>
    </ol>
</template>
<script>
import BaselineArtifact from "./BaselineArtifact.vue";

export default {
    name: "BaselineArtifacts",

    components: { BaselineArtifact },

    props: {
        artifacts: { required: false, type: Array },
        baseline_id: { required: true, type: Number },
        current_depth: { required: true, type: Number }
    },

    beforeCreate() {
        this.$options.components.BaselineArtifact = BaselineArtifact;
    }
};
</script>
