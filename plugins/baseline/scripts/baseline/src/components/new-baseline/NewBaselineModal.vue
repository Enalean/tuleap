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
    <form v-on:submit.prevent="saveBaseline()">
        <div class="tlp-modal-body">
            <div class="tlp-alert-danger" data-test-type="error-message" v-if="is_loading_failed">
                <translate>Cannot fetch milestones</translate>
            </div>
            <div class="tlp-alert-danger" data-test-type="error-message" v-if="is_creating_failed">
                <translate>Cannot create baseline</translate>
            </div>

            <div class="tlp-form-element" data-test-type="input-error-message">
                <label class="tlp-label" for="name">
                    <translate>Name</translate>
                    <i class="fa fa-asterisk"></i>
                </label>
                <input
                    ref="name-input"
                    v-model="name"
                    class="tlp-input"
                    type="text"
                    name="name"
                    id="name"
                    required
                />
            </div>

            <div class="tlp-form-element">
                <label class="tlp-label baseline-modal-milestone-label">
                    <translate>Milestone</translate>
                    <i class="fa fa-asterisk"></i>
                    <span
                        class="tlp-tooltip tlp-tooltip-right"
                        v-bind:data-tlp-tooltip="milestone_tooltip"
                    >
                        <i class="fa fa-question-circle baseline-tooltip-icon"></i>
                    </span>
                </label>
                <milestones-select-skeleton v-if="is_loading" />
                <span
                    class="tlp-text-muted"
                    data-test-type="information_message"
                    v-else-if="is_loading_failed"
                >
                    <translate>Cannot fetch milestones</translate>
                </span>
                <milestones-select
                    v-else-if="available_milestones !== null"
                    v-bind:milestones="available_milestones"
                    v-on:change="selectMilestoneSelected"
                />
            </div>

            <div class="tlp-form-element">
                <label for="snapshot_date" class="tlp-label">
                    <translate>Snapshot date</translate>
                    <span
                        class="tlp-tooltip tlp-tooltip-right"
                        v-bind:data-tlp-tooltip="snapshot_date_tooltip"
                    >
                        <i class="fa fa-question-circle baseline-tooltip-icon-optional"></i>
                    </span>
                </label>
                <div class="tlp-form-element tlp-form-element-prepend">
                    <span class="tlp-prepend">
                        <i class="fa fa-calendar"></i>
                    </span>
                    <input
                        type="text"
                        id="snapshot_date"
                        ref="snapshot_date"
                        class="tlp-input tlp-input-date"
                        data-enabletime="true"
                        size="19"
                    />
                </div>
            </div>
        </div>

        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                data-test-action="cancel"
                v-bind:disabled="is_creating"
            >
                <translate>Cancel</translate>
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                data-test-action="submit"
                v-bind:disabled="is_loading || !some_milestone_available || is_creating"
            >
                <i
                    data-test-type="spinner"
                    class="tlp-button-icon fa fa-fw fa-spinner fa-spin"
                    v-if="is_creating"
                ></i>
                <i class="fa fa-fw fa-save tlp-button-icon" v-else></i>
                <translate>Create baseline</translate>
            </button>
        </div>
    </form>
</template>

<script>
import { getOpenMilestones, createBaseline } from "../../api/rest-querier";
import MilestonesSelect from "./MilestonesSelect.vue";
import MilestonesSelectSkeleton from "./MilestonesSelectSkeleton.vue";
import { datePicker as createDatePicker } from "tlp";

export default {
    name: "NewBaselineModal",

    components: { MilestonesSelect, MilestonesSelectSkeleton },

    props: {
        project_id: { mandatory: true, type: Number },
    },

    data() {
        return {
            name: null,
            milestone: null,
            snapshot_date: null,
            available_milestones: null,
            is_loading_failed: false,
            is_loading: false,
            is_creating_failed: false,
            is_creating: false,
        };
    },

    computed: {
        some_milestone_available() {
            return this.available_milestones && this.available_milestones.length > 0;
        },
        milestone_tooltip() {
            return this.$gettext("Only open milestone are visible here");
        },
        snapshot_date_tooltip() {
            return this.$gettext(
                "Without date, the baseline will be created with the current date",
            );
        },
    },

    mounted() {
        this.fetchMilestones();
        this.createDatePicker();
    },

    methods: {
        async fetchMilestones() {
            this.is_loading = true;
            this.available_milestones = null;
            this.is_loading_failed = false;

            try {
                this.available_milestones = await getOpenMilestones(this.project_id);
            } catch (e) {
                this.is_loading_failed = true;
            } finally {
                this.is_loading = false;
            }
        },

        selectMilestoneSelected(milestone) {
            this.milestone = milestone;
        },

        async saveBaseline() {
            this.is_creating = true;
            this.is_creating_failed = false;

            try {
                await createBaseline(this.name, this.milestone, this.snapshot_date);
                const notification = {
                    text: this.$gettext("The baseline was created"),
                    class: "success",
                };
                this.$store.commit("dialog_interface/notify", notification);
                this.$store.dispatch("baselines/load", { project_id: this.project_id });
                this.$store.commit("dialog_interface/hideModal");
            } catch (e) {
                this.is_creating_failed = true;
            } finally {
                this.is_creating = false;
            }
        },

        createDatePicker() {
            createDatePicker(this.$refs.snapshot_date, {
                maxDate: "today",
                onValueUpdate: (_, date) => {
                    this.snapshot_date = date;
                },
            });
        },
    },
};
</script>
