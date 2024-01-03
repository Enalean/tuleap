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
  -->

<template>
    <div>
        <section class="tlp-pane">
            <div class="tlp-pane-container">
                <div class="tlp-pane-header">
                    <h1 class="tlp-pane-title">
                        <i class="fa fa-tlp-baseline"></i>
                        <span v-translate>your baselines</span>
                        <span
                            v-if="baselines !== null"
                            class="tlp-badge-secondary tlp-badge-outline tlp-badge-rounded tlp-tooltip tlp-tooltip-right baselines-count"
                            v-bind:data-tlp-tooltip="baselines_tooltip"
                        >
                            {{ baselines.length }}
                        </span>
                    </h1>
                </div>

                <section class="tlp-pane-section">
                    <div class="tlp-table-actions">
                        <button
                            type="button"
                            data-target="new-baseline-modal"
                            class="tlp-button-primary"
                            data-test-action="new-baseline"
                            v-on:click="showNewBaselineModal()"
                            v-if="is_admin"
                        >
                            <i class="fa fa-plus tlp-button-icon"></i>
                            <translate>New baseline</translate>
                        </button>
                    </div>
                    <baselines-list v-bind:project_id="project_id" />
                </section>
            </div>
        </section>

        <section class="tlp-pane">
            <div class="tlp-pane-container">
                <div class="tlp-pane-header">
                    <h1 class="tlp-pane-title">
                        <i class="fa fa-tlp-baseline-comparison"></i>
                        <span v-translate>Comparisons</span>
                        <span
                            v-if="comparisons !== null"
                            class="tlp-badge-secondary tlp-badge-outline tlp-badge-rounded tlp-tooltip tlp-tooltip-right comparisons-count"
                            v-bind:data-tlp-tooltip="comparisons_tooltip"
                        >
                            {{ comparisons.length }}
                        </span>
                    </h1>
                </div>

                <section class="tlp-pane-section">
                    <div class="tlp-table-actions">
                        <button
                            type="button"
                            data-target="new-comparison-modal"
                            class="tlp-button-primary"
                            data-test-action="show-comparison"
                            v-bind:disabled="are_baselines_loading || !are_baselines_available"
                            v-on:click="showNewComparisonModal()"
                            v-if="is_admin"
                            v-translate
                        >
                            Compare baselines
                        </button>
                    </div>
                    <comparisons-list v-bind:project_id="project_id" />
                </section>
            </div>
        </section>
    </div>
</template>

<script>
import BaselinesList from "./baselines-list/BaselinesList.vue";
import NewBaselineModal from "./new-baseline/NewBaselineModal.vue";
import NewComparisonModal from "./comparison/NewComparisonModal.vue";
import ComparisonsList from "./comparisons-list/ComparisonsList.vue";
import { mapState } from "vuex";

export default {
    name: "IndexPage",

    components: { BaselinesList, ComparisonsList },

    inject: ["is_admin"],

    props: {
        project_id: { mandatory: true, type: Number },
    },

    data() {
        return {
            new_baseline_modal: null,
            new_comparison_modal: null,
        };
    },

    computed: {
        ...mapState("baselines", ["baselines", "are_baselines_loading"]),
        ...mapState("comparisons", ["comparisons"]),
        baselines_tooltip() {
            return this.$gettext("Baselines available");
        },
        comparisons_tooltip() {
            return this.$gettext("Comparisons available");
        },
        are_baselines_available() {
            return (
                !this.are_baselines_loading && this.baselines !== null && this.baselines.length > 0
            );
        },
    },

    created() {
        this.$emit("title", this.$gettext("Baselines"));
    },

    methods: {
        showNewBaselineModal() {
            this.$store.commit("dialog_interface/showModal", {
                component: NewBaselineModal,
                title: this.$gettext("New baseline"),
                props: { project_id: this.project_id },
            });
        },

        showNewComparisonModal() {
            this.$store.commit("dialog_interface/showModal", {
                component: NewComparisonModal,
                title: this.$gettext("New comparison"),
                props: { baselines: this.baselines },
            });
        },
    },
};
</script>
