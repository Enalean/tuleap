<!--
  - Copyright (c) Enalean, 2018 - 2019. All Rights Reserved.
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
        class="tlp-modal tlp-modal-danger"
        role="dialog"
        aria-labelledby="modal-confirm-change-field-label"
        data-test="change-field-confirmation-modal"
        ref="modal"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="modal-confirm-change-field-label" v-translate>
                Change or remove field
            </h1>
            <div
                class="tlp-modal-close"
                tabindex="0"
                role="button"
                data-dismiss="modal"
                aria-label="Close"
            >
                &times;
            </div>
        </div>
        <div class="tlp-modal-body">
            <p v-translate>
                Are you sure you want to change the field used to define the transitions?
            </p>
            <p v-translate>This action will delete all transition rules! It is irreversible.</p>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-danger tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                v-translate
            >
                Cancel
            </button>
            <button
                type="button"
                class="tlp-button-danger tlp-modal-action"
                v-on:click="props.confirm()"
                v-bind:disabled="props.is_operation_running"
                data-test="confirm-button"
            >
                <i
                    v-if="props.is_operation_running"
                    class="tlp-button-icon fa fa-circle-o-notch fa-spin"
                    data-test="confirm-button-spinner"
                ></i>
                <translate>Confirm</translate>
            </button>
        </div>
    </div>
</template>
