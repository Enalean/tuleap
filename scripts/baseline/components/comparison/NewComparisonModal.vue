<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
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
            <h2 v-translate class="tlp-modal-subtitle">
                Please choose two baselines to compare
            </h2>

            <div class="new-comparison-modal-body">
                <div class="tlp-form-element new-comparison-modal-body-form-element">
                    <h3 v-translate>
                        Reference…
                    </h3>
                    <div class="new-comparison-modal-baselines-list-scrollbar">
                        <label
                            v-for="baseline in baselines"
                            v-bind:key="baseline.id"
                            class="tlp-label tlp-radio"
                        >
                            <input
                                type="radio"
                                name="base"
                                v-bind:value="baseline"
                                v-model="base_baseline"
                                required
                            >
                            {{ baseline.name }}
                        </label>
                    </div>
                </div>

                <div class="tlp-form-element new-comparison-modal-body-form-element">
                    <h3 v-translate>
                        … compared to
                    </h3>
                    <span
                        v-if="baselines_to_compare === null"
                        class="baseline-empty-information-message"
                        v-translate
                    >
                        Please choose a reference baseline
                    </span>
                    <span
                        v-else-if="baselines_to_compare.length === 0"
                        class="baseline-empty-information-message"
                        v-translate
                    >
                        No other baseline available on same artifact.
                    </span>

                    <div v-else class="new-comparison-modal-baselines-list-scrollbar">
                        <label
                            v-for="baseline in baselines_to_compare"
                            v-bind:key="baseline.id"
                            class="tlp-label tlp-radio"
                        >
                            <input
                                type="radio"
                                name="baseline_to_compare"
                                v-bind:value="baseline"
                                v-model="baseline_to_compare"
                                required
                            >
                            {{ baseline.name }}
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
            >
                <translate>Cancel</translate>
            </button>
            <button
                v-bind:disabled="!is_form_valid"
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                data-dismiss="modal"
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
        baselines: { required: true, type: Array }
    },

    data() {
        return {
            base_baseline: null,
            baseline_to_compare: null
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
                .filter(baseline => baseline.artifact_id === this.base_baseline.artifact_id)
                .filter(baseline => baseline !== this.base_baseline);
        }
    },

    methods: {
        openComparison() {
            this.$router.push({
                name: "ComparisonPage",
                params: {
                    from_baseline_id: this.base_baseline.id,
                    to_baseline_id: this.baseline_to_compare.id
                }
            });
        }
    }
};
</script>
