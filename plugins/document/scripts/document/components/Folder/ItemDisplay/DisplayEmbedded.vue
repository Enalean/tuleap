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
    <display-embedded-content v-bind:embedded_file="embedded_file" v-if="has_loaded_without_error" data-test="embedded_content"/>
    <display-embedded-spinner v-else-if="is_loading" data-test="embedded_spinner"/>
</template>

<script>
import { mapActions, mapGetters } from "vuex";
import DisplayEmbeddedSpinner from "./DisplayEmbeddedSpinner.vue";
import DisplayEmbeddedContent from "./DisplayEmbeddedContent.vue";

export default {
    name: "DisplayEmbedded",
    components: { DisplayEmbeddedContent, DisplayEmbeddedSpinner },
    data() {
        return {
            embedded_file: {},
            is_loading: false
        };
    },
    computed: {
        ...mapGetters("error", ["does_document_have_any_error"]),
        has_loaded_without_error() {
            return !this.does_document_have_any_error && !this.is_loading;
        }
    },
    async mounted() {
        this.is_loading = true;
        this.embedded_file = await this.$store.dispatch(
            "loadDocumentWithAscendentHierarchy",
            parseInt(this.$route.params.item_id, 10)
        );
        this.$store.commit("updateCurrentlyDisplayedItem", this.embedded_file);
        this.is_loading = false;
    },
    destroyed() {
        this.$store.commit("updateCurrentlyDisplayedItem", null);
    },
    methods: {
        ...mapActions(["loadDocumentWithAscendentHierarchy"])
    }
};
</script>
