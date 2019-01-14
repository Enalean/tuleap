<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
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
    <error-modal>
        <translate
            tag="p"
            v-bind:translate-params="{size: max_size_upload_in_mb}"
        >
            You are not allowed to upload files bigger than %{ size }.
        </translate>
    </error-modal>
</template>

<script>
import { mapState } from "vuex";
import ErrorModal from "./ErrorModal.vue";
import prettyKibibytes from "pretty-kibibytes";

export default {
    components: { ErrorModal },
    computed: {
        ...mapState(["max_size_upload"]),
        max_size_upload_in_mb() {
            return prettyKibibytes(this.max_size_upload);
        }
    }
};
</script>
