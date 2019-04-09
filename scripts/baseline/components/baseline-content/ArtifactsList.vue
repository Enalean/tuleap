<template>
    <ol class="baseline-content-artifact-ol">
        <li
            v-for="artifact in artifacts" v-bind:key="artifact.id"
            class="baseline-content-artifact-li"
        >
            <artifact
                v-bind:artifact="artifact"
            />
        </li>
    </ol>
</template>
<script>
import Artifact from "./Artifact.vue";

export default {
    name: "ArtifactList",

    components: { Artifact },

    props: {
        artifacts: { required: true, type: Array }
    },

    beforeCreate() {
        this.$options.components.Artifact = Artifact;
    }
};
</script>
