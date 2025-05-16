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
        <p>{{ $gettext("Please start again.") }}</p>
    </error-modal>
</template>

<script>
import { mapState } from "vuex";
import ErrorModal from "./ErrorModal.vue";
import { useGettext } from "vue3-gettext";

const { interpolate, $ngettext } = useGettext();

export default {
    components: { ErrorModal },
    computed: {
        ...mapState("configuration", ["max_files_dragndrop"]),
        error_message() {
            const translated = $ngettext(
                "You are not allowed to drag 'n drop more than %{ nb } file at once.",
                "You are not allowed to drag 'n drop more than %{ nb } files at once.",
                this.max_files_dragndrop,
            );

            return interpolate(translated, { nb: this.max_files_dragndrop });
        },
    },
    methods: {
        bubbleErrorModalHidden() {
            this.$emit("error-modal-hidden");
        },
    },
};
</script>
