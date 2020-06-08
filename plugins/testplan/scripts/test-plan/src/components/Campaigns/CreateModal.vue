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
        <div class="tlp-modal-body">
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
                />
            </div>
            <div class="tlp-form-element">
                <label class="tlp-label" for="new-campaign-tests-selector">
                    <translate>Tests</translate>
                    <i class="fa fa-asterisk" aria-hidden="true"></i>
                </label>
                <select
                    class="tlp-select"
                    id="new-campaign-tests-selector"
                    v-model="test_selector"
                    required
                >
                    <option value="none" v-translate>No tests</option>
                    <option value="all" v-translate>All tests</option>
                    <option value="milestone" v-translate="{ milestone_title }" selected>
                        All tests in %{ milestone_title }
                    </option>
                </select>
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                v-translate
            >
                Close
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                v-bind:disabled="is_creating"
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
import { Modal, modal as createModal } from "tlp";
import { namespace, State } from "vuex-class";
import { CreateCampaignPayload } from "../../store/campaign/type";

const campaign = namespace("campaign");

@Component
export default class CreateModal extends Vue {
    @State
    readonly milestone_title!: string;

    @campaign.Action
    readonly createCampaign!: (payload: CreateCampaignPayload) => Promise<void>;

    private label = "";
    private test_selector: "all" | "none" | "milestone" = "milestone";
    private is_creating = false;
    private modal!: Modal;

    mounted(): void {
        this.modal = createModal(this.$el, { destroy_on_hide: true });
        this.modal.show();
    }

    async submit(): Promise<void> {
        this.is_creating = true;
        try {
            await this.createCampaign({
                label: this.label,
                test_selector: this.test_selector,
            });
        } finally {
            this.is_creating = false;
            this.modal.hide();
        }
    }

    get icon_class(): string {
        if (this.is_creating) {
            return "fa-spin fa-circle-o-notch";
        }

        return "fa-save";
    }
}
</script>
