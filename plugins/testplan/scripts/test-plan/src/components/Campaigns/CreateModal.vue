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
    <div class="tlp-modal" role="dialog" aria-labelledby="test-plan-create-campaign-modal-title">
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
                    <translate>Campaign label</translate>
                    <i class="fa fa-asterisk" aria-hidden="true"></i>
                </label>
                <input type="text" class="tlp-input" id="new-campaign-label" />
            </div>
            <div class="tlp-form-element">
                <label class="tlp-label" for="new-campaign-tests-selector">
                    <translate>Choose tests</translate>
                    <i class="fa fa-asterisk" aria-hidden="true"></i>
                </label>
                <select class="tlp-select" id="new-campaign-tests-selector">
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
            <button type="button" class="tlp-button-primary tlp-modal-action">
                <i class="fa fa-save tlp-button-icon" aria-hidden="true"></i>
                <translate>Create campaign</translate>
            </button>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { modal as createModal } from "tlp";
import { State } from "vuex-class";

@Component
export default class CreateModal extends Vue {
    @State
    readonly milestone_title!: string;

    mounted(): void {
        createModal(this.$el, { destroy_on_hide: true }).show();
    }
}
</script>
