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
        <translate
            tag="p"
            v-bind:translate-n="nb_dropped_files"
            translate-plural="The items you have dropped contain an item which is not a file. If you want to upload a folder, we advise you to create a new folder first, then upload its content inside in a second time."
        >
            The item you have dropped is not a file. If you want to upload a folder, we advise you
            to create a new folder first, then upload its content inside in a second time.
        </translate>
    </error-modal>
</template>

<script>
import ErrorModal from "./ErrorModal.vue";
export default {
    components: { ErrorModal },
    props: {
        reasons: Array,
    },
    computed: {
        nb_dropped_files() {
            return this.reasons[0].nb_dropped_files;
        },
    },
    methods: {
        bubbleErrorModalHidden() {
            this.$emit("error-modal-hidden");
        },
    },
};
</script>
