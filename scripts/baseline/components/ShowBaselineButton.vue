<template>
    <baseline-button
        icon="eye"
        v-on:click="showBaseline()"
    >
        <span v-translate>Consult</span>
    </baseline-button>
</template>
<script>
import BaselineButton from "./BaselineButton.vue";
export default {
    name: "ShowBaselineButton",
    components: { BaselineButton },
    props: {
        baseline: {
            required: true,
            type: Object
        }
    },
    methods: {
        showBaseline() {
            this.$router.push({
                name: "BaselineContentPage",
                params: { baseline_id: this.baseline.id }
            });
        }
    }
};
</script>
