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
    <div>
        <div class="tlp-modal-body">
            <div class="tlp-alert-danger" data-test-type="error-message" v-if="is_deleting_failed">
                <template v-if="Boolean(failed_message)">
                    {{ failed_message }}
                </template>
                <template v-else>
                    {{ default_failed_message }}
                </template>
            </div>
            <p>
                <slot></slot>
                <br />
                <span v-translate>Please confirm your action.</span>
            </p>
        </div>

        <div class="tlp-modal-footer">
            <button
                class="tlp-button-danger tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                type="button"
                v-bind:disabled="is_deleting"
                v-translate
            >
                Cancel
            </button>
            <button
                class="tlp-button-danger tlp-modal-action"
                type="button"
                data-test-action="confirm"
                v-bind:disabled="is_deleting"
                v-on:click="confirm()"
            >
                <i
                    v-if="is_deleting"
                    class="tlp-button-icon fa fa-fw fa-spinner fa-spin"
                    data-test-type="spinner"
                ></i>
                <i class="fa fa-fw fa-trash-o tlp-button-icon" v-else></i>
                {{ submit_label }}
            </button>
        </div>
    </div>
</template>

<script>
import { getMessageFromException } from "../../support/rest-utils";
export default {
    name: "DeleteConfirmationModal",

    props: {
        submit_label: { required: true, type: String },
        default_failed_message: { required: true, type: String },
        on_submit: { required: true, type: Function },
    },

    data() {
        return {
            is_deleting: false,
            is_deleting_failed: false,
            failed_message: null,
        };
    },

    methods: {
        async confirm() {
            this.is_deleting = true;
            this.is_deleting_failed = false;
            try {
                await this.on_submit();
            } catch (exception) {
                this.is_deleting_failed = true;
                this.failed_message = await getMessageFromException(exception);
            } finally {
                this.is_deleting = false;
            }
        },
    },
};
</script>
