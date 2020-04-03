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

<template>
    <div class="tlp-modal-header">
        <h1
            class="tlp-modal-title document-modal-title"
            v-bind:id="ariaLabelledBy"
            v-bind:title="`${$gettext('Close')}`"
        >
            <i class="fa tlp-modal-title-icon" v-bind:class="iconHeaderClass"></i>
            {{ modalTitle }}
        </h1>
        <div
            class="tlp-modal-close"
            tabindex="0"
            role="button"
            data-dismiss="modal"
            v-bind:aria-label="`${$gettext('Close')}`"
        >
            &times;
        </div>
    </div>
</template>
<script>
export default {
    name: "ModalHeader",
    props: {
        modalTitle: String,
        ariaLabelledBy: String,
        iconHeaderClass: String,
    },
};
</script>
