<template>
    <div>
        <transient-comparison-label
            v-if="comparison === null"
            v-bind:from_baseline_id="from_baseline.id"
            v-bind:to_baseline_id="to_baseline.id"
        />
        <comparison-label v-else v-bind:comparison="comparison"/>
        <h2>
            {{ from_baseline.name }}
            <i class="fa fa-tlp-baseline-comparison baseline-comparison-separator"></i>
            {{ to_baseline.name }}
        </h2>
    </div>
</template>

<script>
import TransientComparisonLabel from "./TransientComparisonLabel.vue";
import ComparisonLabel from "./ComparisonLabel.vue";

export default {
    name: "ComparisonHeader",

    components: {
        TransientComparisonLabel,
        ComparisonLabel
    },

    props: {
        comparison: { required: false, type: Object, default: null },
        from_baseline: { required: true, type: Object },
        to_baseline: { required: true, type: Object }
    }
};
</script>
