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
        ref="modal_element"
        class="tlp-modal"
        role="dialog"
        aria-labelledby="test-plan-create-campaign-modal-title"
        v-on:submit.stop.prevent="submit"
    >
        <div class="tlp-modal-header">
            <h1 id="test-plan-create-campaign-modal-title" class="tlp-modal-title">
                {{ $gettext("Create new campaign") }}
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="close_label"
            >
                <i class="fas fa-times tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
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
                        {{ $gettext("Name") }}
                        <i class="fa fa-asterisk" aria-hidden="true"></i>
                    </label>
                    <input
                        id="new-campaign-label"
                        v-model="campaign_label"
                        type="text"
                        class="tlp-input"
                        required
                        data-test="new-campaign-label"
                    />
                </div>
                <create-campaign-test-selector
                    v-model:initial_tests="initial_tests"
                    v-bind:testdefinition_tracker_reports="testdefinition_tracker_reports"
                />
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                v-bind:disabled="is_creating || testdefinition_tracker_reports === null"
                data-test="new-campaign-submit-button"
            >
                <i class="fa tlp-button-icon" v-bind:class="icon_class" aria-hidden="true"></i>
                {{ $gettext("Create campaign") }}
            </button>
        </div>
    </form>
</template>
<script setup lang="ts">
import CreateCampaignTestSelector from "./CreateCampaignTestSelector.vue";
import CreateModalErrorFeedback from "./CreateModalErrorFeedback.vue";
import { useNamespacedActions, useState } from "vuex-composition-helpers";
import type { State } from "../../store/type";
import type { CampaignActions } from "../../store/campaign/campaign-actions";
import type { BacklogItemActions } from "../../store/backlog-item/backlog-item-actions";
import { computed, onMounted, ref } from "vue";
import type { Modal } from "tlp";
import { createModal } from "tlp";
import { FetchWrapperError } from "@tuleap/tlp-fetch";
import type { TrackerReport } from "../../helpers/Campaigns/tracker-reports-retriever";
import { getTrackerReports } from "../../helpers/Campaigns/tracker-reports-retriever";
import { useGettext } from "vue3-gettext";
import type { CampaignInitialTests } from "../../helpers/Campaigns/campaign-initial-tests";

const { testdefinition_tracker_id } = useState<Pick<State, "testdefinition_tracker_id">>([
    "testdefinition_tracker_id",
]);

const { createCampaign } = useNamespacedActions<Pick<CampaignActions, "createCampaign">>(
    "campaign",
    ["createCampaign"],
);
const { loadBacklogItems } = useNamespacedActions<Pick<BacklogItemActions, "loadBacklogItems">>(
    "backlog_item",
    ["loadBacklogItems"],
);

const testdefinition_tracker_reports = ref<TrackerReport[] | null>(null);
const campaign_label = ref("");
const initial_tests = ref<CampaignInitialTests>({ test_selector: "milestone" });
const is_creating = ref(false);
const error_message = ref("");
const error_message_details = ref("");

const modal_element = ref<InstanceType<typeof Element>>();
let modal: Modal | null = null;

const { $gettext } = useGettext();

const close_label = ref($gettext("Close"));

onMounted(async (): Promise<void> => {
    if (!modal_element.value) {
        return;
    }

    modal = createModal(modal_element.value, { destroy_on_hide: true });
    modal.show();
    if (testdefinition_tracker_id.value === null) {
        testdefinition_tracker_reports.value = [];
    } else {
        try {
            testdefinition_tracker_reports.value = await getTrackerReports(
                testdefinition_tracker_id.value,
            );
        } catch (e) {
            error_message.value = $gettext(
                "The retrieval of the test definition tracker reports has failed, try again later",
            );
            throw e;
        }
    }
});

async function submit(): Promise<void> {
    is_creating.value = true;
    try {
        await createCampaign({
            label: campaign_label.value,
            initial_tests: initial_tests.value,
        });
        modal?.hide();
    } catch (e) {
        error_message.value = $gettext("An error occurred while creating the campaign.");
        try {
            error_message_details.value = await getErrorMessageDetailsFromError(e);
        } catch (error) {
            error_message_details.value = "";
        }
        throw e;
    } finally {
        is_creating.value = false;
    }
    await loadBacklogItems();
}

async function getErrorMessageDetailsFromError(error: unknown): Promise<string> {
    if (!(error instanceof FetchWrapperError)) {
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

const icon_class = computed((): string => {
    if (is_creating.value) {
        return "fa-spin fa-circle-o-notch";
    }

    return "fa-save";
});
</script>
