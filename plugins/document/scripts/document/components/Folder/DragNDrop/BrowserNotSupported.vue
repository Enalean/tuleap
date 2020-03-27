<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

<template>
    <error-modal v-on:error-modal-hidden="bubbleErrorModalHidden">
        <p v-translate>
            Drag and drop is not supported under IE11. Please use a more recent browser.
        </p>
    </error-modal>
</template>

<script>
import ErrorModal from "./ErrorModal.vue";
export default {
    name: "BrowserNotSupported",
    components: { ErrorModal },
    methods: {
        bubbleErrorModalHidden() {
            this.$emit("error-modal-hidden");
        },
    },
};
</script>
