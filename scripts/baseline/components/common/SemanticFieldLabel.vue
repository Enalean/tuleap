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
  -
  -->

<template>
    <div>
        <template v-if="is_semantic_field_label_available">
            {{ semantic_field_label }}
        </template>
        <span v-else class="tlp-skeleton-text" data-test-type="skeleton"></span>
    </div>
</template>

<script>
export default {
    name: "SemanticFieldLabel",

    props: {
        semantic: {
            required: true,
            type: String
        },
        tracker_id: {
            required: true,
            type: Number
        }
    },

    computed: {
        is_semantic_field_label_available() {
            return (
                this.$store.state.is_semantic_fields_by_tracker_id_loading[this.tracker_id] ===
                    false && this.semantic_field_label !== null
            );
        },
        semantic_field_label() {
            if (
                !this.$store.state.semantic_fields_by_tracker_id.hasOwnProperty(this.tracker_id) ||
                !this.$store.state.semantic_fields_by_tracker_id[this.tracker_id].hasOwnProperty(
                    this.semantic
                )
            ) {
                return null;
            }
            return this.$store.state.semantic_fields_by_tracker_id[this.tracker_id][this.semantic]
                .label;
        }
    },

    mounted() {
        this.loadSemanticField();
    },

    methods: {
        loadSemanticField() {
            this.$store.dispatch("loadSemanticFields", this.tracker_id);
        }
    }
};
</script>
