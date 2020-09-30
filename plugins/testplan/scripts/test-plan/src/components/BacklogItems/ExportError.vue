<!--
  - Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
    <div
        class="tlp-modal tlp-modal-danger"
        role="dialog"
        aria-labelledby="testplan-export-error-modal-title"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="testplan-export-error-modal-title">
                <i class="fas fa-exclamation-triangle tlp-modal-title-icon"></i>
                <translate>Oops, there's an issue</translate>
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
            <p v-translate>The export did not succeed, please try again.</p>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-danger tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                v-translate
            >
                Close
            </button>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { createModal } from "tlp";

@Component
export default class ExportError extends Vue {
    mounted(): void {
        createModal(this.$el, { destroy_on_hide: true }).show();
    }
}
</script>
