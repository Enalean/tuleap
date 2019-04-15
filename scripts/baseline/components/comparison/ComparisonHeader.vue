<template>
    <div>
        <div
            v-if="is_loading_failed"
            class="tlp-alert-danger tlp-framed-vertically"
            v-translate
        >
            Cannot fetch baselines
        </div>
        <h2 v-else-if="is_loading">
            <span class="tlp-skeleton-text"></span>
            <i class="fa fa-tlp-baseline-comparison baseline-comparison-separator"></i>
            <span class="tlp-skeleton-text"></span>
        </h2>
        <h2 v-else>
            {{ from_baseline.name }}
            <i class="fa fa-tlp-baseline-comparison baseline-comparison-separator"></i>
            {{ to_baseline.name }}
        </h2>
    </div>
</template>

<script>
import { getBaseline } from "../../api/rest-querier";

export default {
    name: "ComparisonHeader",

    props: {
        from_baseline_id: { required: true, type: Number },
        to_baseline_id: { required: true, type: Number }
    },

    data() {
        return {
            from_baseline: null,
            to_baseline: null,
            is_loading: true,
            is_loading_failed: false
        };
    },

    mounted() {
        this.fetchBaselines();
    },

    methods: {
        async fetchBaselines() {
            this.is_loading = true;
            this.is_loading_failed = false;

            try {
                const from_baseline = getBaseline(this.from_baseline_id);
                const to_baseline = getBaseline(this.to_baseline_id);
                this.to_baseline = await to_baseline;
                this.from_baseline = await from_baseline;
            } catch (e) {
                this.is_loading_failed = true;
            } finally {
                this.is_loading = false;
            }
        }
    }
};
</script>
