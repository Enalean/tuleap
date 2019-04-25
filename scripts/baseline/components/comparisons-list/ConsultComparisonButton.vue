<template>
    <action-button
        icon="eye"
        v-on:click="showComparison()"
    >
        <span v-translate>
            Consult
        </span>
    </action-button>
</template>
<script>
import ActionButton from "../common/ActionButton.vue";

export default {
    name: "ConsultComparisonButton",
    components: { ActionButton },
    props: {
        comparison: {
            required: true,
            type: Object
        }
    },
    methods: {
        showComparison() {
            this.$router.push({
                name: "ComparisonPage",
                params: { comparison_id: this.comparison.id }
            });
        }
    }
};
</script>
