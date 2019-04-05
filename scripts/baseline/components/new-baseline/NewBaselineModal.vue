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
    <modal v-bind:title="title" class="new-baseline-modal">
        <form v-on:submit.prevent="saveBaseline()">
            <div class="tlp-modal-body">
                <div
                    class="tlp-alert-danger"
                    data-test-type="error-message"
                    v-if="is_loading_failed"
                >
                    <translate>Cannot fetch milestones</translate>
                </div>
                <div
                    class="tlp-alert-danger"
                    data-test-type="error-message"
                    v-if="is_creating_failed"
                >
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
                    >
                </div>

                <div class="tlp-form-element">
                    <label class="tlp-label baseline-modal-milestone-label">
                        <translate>Milestone</translate>
                        <i class="fa fa-asterisk"></i>
                        <span
                            class="tlp-tooltip tlp-tooltip-right"
                            v-bind:data-tlp-tooltip="milestone_tooltip"
                        >
                            <i
                                class="fa fa-question-circle baseline-tooltip-icon"
                            ></i>
                        </span>
                    </label>
                    <milestones-select-skeleton v-if="is_loading"/>
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
                    type="submit"
                    class="tlp-button-primary tlp-modal-action"
                    v-bind:disabled="is_loading || !some_milestone_available || is_creating"
                >
                    <i
                        data-test-type="spinner"
                        class="tlp-button-icon fa fa-spinner fa-spin"
                        v-if="is_creating"
                    >
                    </i>
                    <i class="fa fa-save tlp-button-icon" v-else>
                    </i>
                    <translate>Create baseline</translate>
                </button>
            </div>
        </form>
    </modal>
</template>

<script>
import { getOpenMilestones, createBaseline } from "../../api/rest-querier";
import MilestonesSelect from "./MilestonesSelect.vue";
import MilestonesSelectSkeleton from "./MilestonesSelectSkeleton.vue";
import Modal from "../common/Modal.vue";

export default {
    name: "NewBaselineModal",

    components: { Modal, MilestonesSelect, MilestonesSelectSkeleton },

    props: {
        project_id: { mandatory: true, type: Number }
    },

    data() {
        return {
            name: null,
            milestone: null,
            available_milestones: null,
            is_loading_failed: false,
            is_loading: false,
            is_creating_failed: false,
            is_creating: false
        };
    },

    computed: {
        title() {
            return this.$gettext("New baseline");
        },
        some_milestone_available() {
            return this.available_milestones !== null && this.available_milestones.length > 0;
        },
        milestone_tooltip() {
            return this.$gettext("Only open milestone are visible here");
        }
    },

    methods: {
        reload() {
            this.name = null;
            this.milestone = null;
            this.is_creating_failed = false;
            this.fetchMilestones();
        },

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
                await createBaseline(this.name, this.milestone);
                const notification = {
                    text: this.$gettext("The baseline was created"),
                    class: "success"
                };
                this.$store.commit("notify", notification);
                this.$emit("created");
            } catch (e) {
                this.is_creating_failed = true;
            } finally {
                this.is_creating = false;
            }
        }
    }
};
</script>
