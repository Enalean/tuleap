<template>
    <div>
        <div
            v-if="is_loading_failed"
            class="tlp-alert-danger tlp-framed-vertically"
            v-translate
        >
            Cannot fetch baselines
        </div>
        <template v-else-if="is_loading">
            <baseline-label-skeleton key="from"/>
            <baseline-label-skeleton key="to"/>
        </template>
        <template v-else>
            <baseline-label v-bind:baseline="from_baseline" key="from"/>
            <baseline-label v-bind:baseline="to_baseline" key="to"/>
        </template>
    </div>
</template>

<script>
import { getBaseline } from "../../api/rest-querier";
import { presentBaseline } from "../../presenters/baseline";
import BaselineLabel from "../common/BaselineLabel.vue";
import BaselineLabelSkeleton from "../common/BaselineLabelSkeleton.vue";

export default {
    name: "ComparisonHeader",

    components: { BaselineLabel, BaselineLabelSkeleton },

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
                const from_baseline = this.getPresentedBaseline(this.from_baseline_id);
                const to_baseline = this.getPresentedBaseline(this.to_baseline_id);
                this.to_baseline = await to_baseline;
                this.from_baseline = await from_baseline;
            } catch (e) {
                this.is_loading_failed = true;
            } finally {
                this.is_loading = false;
            }
        },

        async getPresentedBaseline(baseline_id) {
            const baseline = await getBaseline(baseline_id);
            return presentBaseline(baseline);
        }
    }
};
</script>
