<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

<template functional>
    <div
        class="tlp-modal tlp-modal-warning"
        role="dialog"
        aria-labelledyby="modal-confirm-workflow-mode-change-label"
        ref="modal"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="modal-confirm-workflow-mode-change-label" v-translate>
                Wait a minute...
            </h1>
            <button class="tlp-modal-close" type="button" data-dismiss="modal" aria-label="Close">
                <i class="fas fa-times tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tlp-modal-body">
            <slot name="modal-body"></slot>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-warning tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                v-translate
            >
                Cancel
            </button>
            <button
                type="button"
                class="tlp-button-warning tlp-modal-action"
                v-on:click="props.confirm()"
                v-bind:disabled="props.is_operation_running"
                data-test="button-switch-to-simple-configuration"
            >
                <i
                    v-if="props.is_operation_running"
                    class="tlp-button-icon fas fa-circle-notch fa-spin"
                ></i>
                <slot name="switch-button-label"></slot>
            </button>
        </div>
    </div>
</template>
