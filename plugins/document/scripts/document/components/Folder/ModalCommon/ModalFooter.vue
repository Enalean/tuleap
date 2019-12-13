<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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

<template functional>
    <div v-bind:id="props.ariaLabelledBy" class="tlp-modal-footer">
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
            v-bind:disabled="props.isLoading"
            data-test="document-modal-submit-button"
        >
            <i
                v-if="props.isLoading"
                class="tlp-button-icon fa fa-spin fa-circle-o-notch"
                data-test="document-modal-footer-spinner"
            ></i>
            <i
                v-else
                class="fa tlp-button-icon"
                v-bind:class="props.iconSubmitButtonClass"
                data-test="document-modal-footer-icon"
            ></i>
            {{ props.submitButtonLabel }}
        </button>
    </div>
</template>
