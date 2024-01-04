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

<script setup lang="ts">
import { getMessageFromException } from "../../support/rest-utils";
import { ref } from "vue";

const props = defineProps<{
    submit_label: string;
    default_failed_message: string;
    on_submit: () => void;
}>();

const is_deleting = ref(false);
const is_deleting_failed = ref(false);
const failed_message = ref<string | null>(null);

async function confirm(): Promise<void> {
    is_deleting.value = true;
    is_deleting_failed.value = false;
    try {
        await props.on_submit();
    } catch (exception) {
        is_deleting_failed.value = true;
        failed_message.value = await getMessageFromException(exception);
    } finally {
        is_deleting.value = false;
    }
}
</script>
