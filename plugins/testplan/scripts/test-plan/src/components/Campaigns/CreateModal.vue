<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <form
        class="tlp-modal"
        role="dialog"
        aria-labelledby="test-plan-create-campaign-modal-title"
        v-on:submit.stop.prevent="submit"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="test-plan-create-campaign-modal-title">
                <i class="fa fa-plus tlp-modal-title-icon" aria-hidden="true"></i>
                <translate>Create new campaign</translate>
            </h1>
            <div
                class="tlp-modal-close"
                tabindex="0"
                role="button"
                data-dismiss="modal"
                v-bind:aria-label="$gettext('Close')"
            >
                ×
            </div>
        </div>
        <create-modal-error-feedback
            v-if="error_message !== ''"
            v-bind:error_message="error_message"
            v-bind:error_message_details="error_message_details"
            data-test="new-campaign-error-message"
        />
        <div class="tlp-modal-body">
            <div
                v-if="testdefinition_tracker_reports === null"
                class="test-plan-campaign-modal-creation-loading"
            >
                <i class="fa fa-spin fa-circle-o-notch"></i>
            </div>
            <div v-else>
                <div class="tlp-form-element">
                    <label class="tlp-label" for="new-campaign-label">
                        <translate>Name</translate>
                        <i class="fa fa-asterisk" aria-hidden="true"></i>
                    </label>
                    <input
                        type="text"
                        class="tlp-input"
                        id="new-campaign-label"
                        v-model="label"
                        required
                        data-test="new-campaign-label"
                    />
                </div>
                <create-campaign-test-selector
                    v-model="initial_tests"
                    v-bind:testdefinition_tracker_reports="testdefinition_tracker_reports"
                />
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                v-translate
            >
                Cancel
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                v-bind:disabled="is_creating || testdefinition_tracker_reports === null"
                data-test="new-campaign-submit-button"
            >
                <i class="fa tlp-button-icon" v-bind:class="icon_class" aria-hidden="true"></i>
                <translate>Create campaign</translate>
            </button>
        </div>
    </form>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { FetchWrapperError, Modal, createModal } from "tlp";
import { namespace, State } from "vuex-class";
import { CreateCampaignPayload } from "../../store/campaign/type";
import CreateCampaignTestSelector from "./CreateCampaignTestSelector.vue";
import CreateModalErrorFeedback from "./CreateModalErrorFeedback.vue";
import {
    TrackerReport,
    getTrackerReports,
} from "../../helpers/Campaigns/tracker-reports-retriever";
import { CampaignInitialTests } from "../../helpers/Campaigns/campaign-initial-tests";

const campaign = namespace("campaign");
const backlog_item = namespace("backlog_item");
@Component({
    components: { CreateModalErrorFeedback, CreateCampaignTestSelector },
})
export default class CreateModal extends Vue {
    @State
    readonly milestone_title!: string;
    @State
    readonly testdefinition_tracker_id!: number | null;

    @campaign.Action
    readonly createCampaign!: (payload: CreateCampaignPayload) => Promise<void>;

    @backlog_item.Action
    readonly loadBacklogItems!: () => Promise<void>;

    private label = "";
    private initial_tests: CampaignInitialTests = { test_selector: "milestone" };
    private testdefinition_tracker_reports: TrackerReport[] | null = null;
    private is_creating = false;
    private modal!: Modal;
    private error_message = "";
    private error_message_details = "";

    async mounted(): Promise<void> {
        this.modal = createModal(this.$el, { destroy_on_hide: true });
        this.modal.show();
        if (this.testdefinition_tracker_id === null) {
            this.testdefinition_tracker_reports = [];
        } else {
            try {
                this.testdefinition_tracker_reports = await getTrackerReports(
                    this.testdefinition_tracker_id
                );
            } catch (e) {
                this.error_message = this.$gettext(
                    "The retrieval of the test definition tracker reports has failed, try again later"
                );
                throw e;
            }
        }
    }

    async submit(): Promise<void> {
        this.is_creating = true;
        try {
            await this.createCampaign({
                label: this.label,
                initial_tests: this.initial_tests,
            });
            this.modal.hide();
        } catch (e) {
            this.error_message = this.$gettext("An error occurred while creating the campaign.");
            try {
                this.error_message_details = await this.getErrorMessageDetailsFromError(e);
            } catch (error) {
                this.error_message_details = "";
            }
            throw e;
        } finally {
            this.is_creating = false;
        }
        await this.loadBacklogItems();
    }

    private async getErrorMessageDetailsFromError(
        error: Error | FetchWrapperError
    ): Promise<string> {
        if (!("response" in error)) {
            return "";
        }

        const json = await error.response.json();
        if (!Object.prototype.hasOwnProperty.call(json, "error")) {
            return "";
        }

        if (Object.prototype.hasOwnProperty.call(json.error, "i18n_error_message")) {
            return json.error.i18n_error_message;
        }

        return json.error.message;
    }

    get icon_class(): string {
        if (this.is_creating) {
            return "fa-spin fa-circle-o-notch";
        }

        return "fa-save";
    }
}
</script>
