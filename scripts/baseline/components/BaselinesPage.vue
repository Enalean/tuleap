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
  -->

<template>
    <main class="tlp-framed-vertically">
        <div class="tlp-framed-horizontally">
            <div class="tlp-card tlp-card-inactive">
                <p class="tlp-text-muted" v-translate>
                    Baselines features allow you to consult the state of your releases in a
                    chosen date in the past.
                </p>
            </div>

            <div
                v-if="is_baseline_created"
                class="tlp-alert-success tlp-framed-vertically"
            >
                <translate>The baseline was created</translate>
            </div>

            <div
                v-if="is_loading_failed"
                class="tlp-alert-danger tlp-framed-vertically"
            >
                <translate>Cannot fetch baselines</translate>
            </div>

            <section class="tlp-pane">
                <div class="tlp-pane-container">
                    <div class="tlp-pane-header baseline-pane-header">
                        <h2>
                            <span class="baselines-title" v-translate>
                                your baselines
                            </span>
                            <span v-if="baselines !== null"
                                  class="tlp-tooltip tlp-tooltip-right"
                                  v-bind:data-tlp-tooltip="baselines_tooltip"
                            >
                                ({{ baselines.length }})
                            </span>
                        </h2>
                        <button
                            type="button"
                            data-target="new-baseline-modal"
                            class="tlp-button-primary"
                            v-on:click="showNewBaselineModal()"
                        >
                            <i class="fa fa-plus tlp-button-icon"></i>
                            <translate>New baseline</translate>
                        </button>

                        <new-baseline-modal
                            id="new-baseline-modal"
                            ref="new_baseline_modal"
                            v-bind:project_id="project_id"
                            v-on:created="onBaselineCreated()"
                        />
                    </div>

                    <section class="tlp-pane-section">
                        <baseline-table v-bind:baselines="baselines" v-bind:is_loading="is_loading"/>
                    </section>
                </div>
            </section>

            <section class="tlp-pane">
                <div class="tlp-pane-container">
                    <div class="tlp-pane-header baseline-pane-header">
                        <h2>
                            <span class="baselines-title" v-translate>
                                Comparisons
                            </span>
                        </h2>
                        <button
                            type="button"
                            data-target="new-comparison-modal"
                            class="tlp-button-primary"
                            v-bind:disabled="is_loading || !are_baselines_available"
                            v-on:click="showNewComparisonModal()"
                        >
                            <i class="fa fa-plus tlp-button-icon"></i>
                            <translate>Compare baselines</translate>
                        </button>

                        <new-comparison-modal
                            v-if="are_baselines_available"
                            id="new-comparison-modal"
                            ref="new_comparison_modal"
                            v-bind:baselines="baselines"
                        />
                    </div>
                </div>
            </section>
        </div>
    </main>
</template>

<script>
import BaselineTable from "./BaselineTable.vue";
import NewBaselineModal from "./new-baseline/NewBaselineModal.vue";
import NewComparisonModal from "./comparison/NewComparisonModal.vue";
import { modal as createModal } from "tlp";
import { getBaselines } from "../api/rest-querier";
import { presentBaselines } from "../presenters/baseline";

export default {
    name: "BaselinesPage",

    components: { NewBaselineModal, BaselineTable, NewComparisonModal },

    props: {
        project_id: { mandatory: true, type: Number }
    },

    data() {
        return {
            is_baseline_created: false,
            baselines: null,
            is_loading: false,
            is_loading_failed: false,
            new_baseline_modal: null,
            new_comparison_modal: null
        };
    },

    computed: {
        baselines_tooltip() {
            return this.$gettext("Baselines available");
        },
        are_baselines_available() {
            return this.baselines !== null && this.baselines.length > 0;
        }
    },

    watch: {
        are_baselines_available: async function(val) {
            if (val) {
                await this.$nextTick();
                this.new_comparison_modal = createModal(this.$refs.new_comparison_modal.$el);
            }
        }
    },

    mounted() {
        this.new_baseline_modal = createModal(this.$refs.new_baseline_modal.$el);
        this.fetchBaselines();
    },

    methods: {
        showNewBaselineModal() {
            this.new_baseline_modal.show();
            this.$refs.new_baseline_modal.reload();
            this.is_baseline_created = false;
        },

        onBaselineCreated() {
            this.fetchBaselines();
            this.is_baseline_created = true;
            this.new_baseline_modal.hide();
        },

        async fetchBaselines() {
            this.baselines = null;
            this.is_loading = true;
            this.is_loading_failed = false;

            try {
                const baselines = await getBaselines(this.project_id);
                this.baselines = await presentBaselines(baselines);
            } catch (e) {
                this.is_loading_failed = true;
            } finally {
                this.is_loading = false;
            }
        },

        showNewComparisonModal() {
            this.new_comparison_modal.show();
            this.$refs.new_comparison_modal.reload();
        }
    }
};
</script>
