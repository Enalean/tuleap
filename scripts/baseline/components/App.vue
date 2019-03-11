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
                    <div class="tlp-pane-header">
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
                            v-on:click="showNewBaselineModal"
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
        </div>
    </main>
</template>

<script>
import BaselineTable from "./BaselineTable.vue";
import NewBaselineModal from "./NewBaselineModal.vue";
import { modal as createModal } from "tlp";
import { getBaselines } from "../api/rest-querier";

export default {
    name: "App",

    components: { NewBaselineModal, BaselineTable },

    props: {
        project_id: { mandatory: true, type: Number }
    },

    data() {
        return {
            is_baseline_created: false,
            baselines: null,
            is_loading: false,
            is_loading_failed: false,
            modal: null
        };
    },

    computed: {
        baselines_tooltip() {
            return this.$gettext("Baselines available");
        }
    },

    mounted() {
        this.modal = createModal(this.$refs.new_baseline_modal.$el);
        this.fetchBaselines();
    },

    methods: {
        showNewBaselineModal() {
            this.modal.show();
            this.$refs.new_baseline_modal.reload();
            this.is_baseline_created = false;
        },

        onBaselineCreated() {
            this.is_baseline_created = true;
            this.modal.hide();
        },

        async fetchBaselines() {
            this.baselines = null;
            this.is_loading = true;
            this.is_loading_failed = false;

            try {
                this.baselines = await getBaselines(this.project_id);
            } catch (e) {
                this.is_loading_failed = true;
            } finally {
                this.is_loading = false;
            }
        }
    }
};
</script>
