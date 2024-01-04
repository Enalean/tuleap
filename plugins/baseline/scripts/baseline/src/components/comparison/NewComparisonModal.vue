<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -
  -->
<template>
    <form v-on:submit.prevent="openComparison()">
        <div class="tlp-modal-body">
            <p v-translate>Please choose two baselines to compare</p>

            <div class="new-comparison-modal-body">
                <div class="tlp-form-element new-comparison-modal-body-form-element">
                    <label class="tlp-label" for="base_baseline">
                        <translate>Reference…</translate>
                        <i class="fa fa-asterisk"></i>
                    </label>
                    <select
                        id="base_baseline"
                        ref="base_baseline"
                        class="tlp-select"
                        name="base_baseline"
                        v-model="base_baseline_id"
                        required
                        data-test-type="date-baseline-select"
                    >
                        <option value="" selected disabled v-translate>Choose a baseline…</option>
                        <option
                            v-for="baseline in baselines"
                            v-bind:key="baseline.id"
                            v-bind:value="baseline.id"
                        >
                            {{ baseline.name }}
                        </option>
                    </select>
                </div>

                <div class="tlp-form-element new-comparison-modal-body-form-element">
                    <label class="tlp-label" for="baseline_to_compare">
                        <translate>… compared to</translate>
                        <i class="fa fa-asterisk"></i>
                    </label>
                    <span
                        v-if="baselines_to_compare !== null && baselines_to_compare.length === 0"
                        class="baseline-empty-information-message"
                        data-test-type="no-baseline-to-compare-message"
                        v-translate
                    >
                        No other baseline available on same artifact.
                    </span>
                    <select
                        v-else
                        id="baseline_to_compare"
                        ref="baseline_to_compare"
                        class="tlp-select"
                        name="baseline_to_compare"
                        v-model="baseline_to_compare_id"
                        required
                    >
                        <option
                            v-if="baselines_to_compare === null"
                            value=""
                            selected
                            disabled
                            key="choose-reference"
                            v-translate
                        >
                            Choose a reference baseline…
                        </option>
                        <option
                            v-else
                            value=""
                            selected
                            disabled
                            key="choose-compare-to"
                            v-translate
                        >
                            Choose a baseline to compare…
                        </option>
                        <option
                            v-for="baseline in baselines_to_compare"
                            v-bind:key="baseline.id"
                            v-bind:value="baseline.id"
                        >
                            {{ baseline.name }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                key="cancel"
            >
                <translate>Cancel</translate>
            </button>
            <button
                v-bind:disabled="!is_form_valid"
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                data-dismiss="modal"
                data-test-action="submit"
                key="submit"
            >
                <translate>Show comparison</translate>
            </button>
        </div>
    </form>
</template>

<script>
export default {
    name: "NewComparisonModal",
    props: {
        baselines: { required: true, type: Array },
    },

    data() {
        return {
            base_baseline_id: null,
            baseline_to_compare_id: null,
        };
    },

    computed: {
        is_form_valid() {
            return this.base_baseline !== null && this.baseline_to_compare !== null;
        },
        baselines_to_compare() {
            if (this.base_baseline === null) {
                return null;
            }
            return this.baselines
                .filter((baseline) => baseline.artifact_id === this.base_baseline.artifact_id)
                .filter((baseline) => baseline !== this.base_baseline);
        },
        base_baseline() {
            return this.findBaselineById(this.base_baseline_id);
        },
        baseline_to_compare() {
            return this.findBaselineById(this.baseline_to_compare_id);
        },
    },

    methods: {
        findBaselineById(id) {
            let found_baseline = this.baselines.find((baseline) => baseline.id === id);
            return found_baseline || null;
        },
        openComparison() {
            this.$router.push({
                name: "TransientComparisonPage",
                params: {
                    from_baseline_id: this.base_baseline.id,
                    to_baseline_id: this.baseline_to_compare.id,
                },
            });
        },
    },
};
</script>
