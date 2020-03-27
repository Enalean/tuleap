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
  -->

<template>
    <div class="tlp-modal-feedback" v-if="is_error">
        <div class="tlp-alert-danger">{{ error_message }}</div>
    </div>
</template>

<script>
import { mapState } from "vuex";

export default {
    name: "ModalErrorFeedback",
    computed: {
        ...mapState("transitionModal", {
            is_error: (state) => state.is_modal_operation_failed,
            error_message: (state) => state.modal_operation_failure_message,
        }),
    },
};
</script>
