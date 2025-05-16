<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <div v-bind:id="ariaLabelledBy" class="tlp-modal-footer">
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
            v-bind:disabled="isLoading"
            v-bind:data-test="dataTest"
        >
            <i
                v-if="isLoading"
                class="tlp-button-icon fa-solid fa-spin fa-circle-notch"
                data-test="document-modal-footer-spinner"
            ></i>
            <i
                v-else
                class="tlp-button-icon"
                v-bind:class="iconSubmitButtonClass"
                data-test="document-modal-footer-icon"
            ></i>
            {{ submitButtonLabel }}
        </button>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();

defineProps<{
    ariaLabelledBy: string;
    isLoading: boolean;
    dataTest: string;
    iconSubmitButtonClass: string;
    submitButtonLabel: string;
}>();
</script>
