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
  -
  -->

<template>
    <div>
        <template v-if="is_label_available">
            {{ label }}
        </template>
        <span v-else class="tlp-skeleton-text" data-test-type="skeleton"></span>
    </div>
</template>

<script>
import { mapGetters } from "vuex";

export default {
    name: "SemanticFieldLabel",

    props: {
        semantic: {
            required: true,
            type: String,
        },
        tracker_id: {
            required: true,
            type: Number,
        },
    },

    computed: {
        ...mapGetters("semantics", ["field_label", "is_field_label_available"]),
        is_label_available() {
            return this.is_field_label_available(this.tracker_id, this.semantic);
        },
        label() {
            return this.field_label(this.tracker_id, this.semantic);
        },
    },

    mounted() {
        this.loadSemanticField();
    },

    methods: {
        loadSemanticField() {
            this.$store.dispatch("semantics/loadByTrackerId", this.tracker_id);
        },
    },
};
</script>
