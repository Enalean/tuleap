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
  -->

<template>
    <error-modal v-on:error-modal-hidden="bubbleErrorModalHidden">
        <p>{{ error_message }}</p>
    </error-modal>
</template>

<script>
import { mapState } from "vuex";
import ErrorModal from "./ErrorModal.vue";
import { sprintf } from "sprintf-js";
import prettyKibibytes from "pretty-kibibytes";

export default {
    components: { ErrorModal },
    computed: {
        ...mapState("configuration", ["max_size_upload"]),
        error_message() {
            return sprintf(
                this.$gettext("You are not allowed to upload files bigger than %s."),
                prettyKibibytes(this.max_size_upload),
            );
        },
    },
    methods: {
        bubbleErrorModalHidden() {
            this.$emit("error-modal-hidden");
        },
    },
};
</script>
