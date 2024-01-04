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
    <select class="tlp-select" tabindex="-1" aria-hidden="true" ref="input" width="100%" multiple>
        <option v-for="tracker in trackers" v-bind:key="tracker.id" v-bind:value="tracker.id">
            {{ tracker.name }}
        </option>
    </select>
</template>
<script>
import { select2 } from "tlp";

export default {
    name: "TrackersMultiSelect",

    props: {
        trackers: {
            required: true,
            type: Array,
        },
    },

    data() {
        return {
            select2_control: null,
        };
    },

    mounted() {
        this.select2_control = select2(this.$refs.input, {
            placeholder: this.$gettext("Choose a tracker…"),
            multiple: true,
        }).on("change", this.onChange);
    },

    destroyed() {
        this.select2_control.off().select2("destroy");
    },

    methods: {
        // Used to convert values returned by select2 before emitting
        // these values to parent in expected type.
        // This is required as select works only with strings.
        // See https://select2.org/data-sources/formats#automatic-string-casting
        convertToInts(values) {
            if (!values) {
                return values;
            }
            return values.map((value) => Number(value));
        },
        findTrackerById(id) {
            return this.trackers.find((tracker) => tracker.id === id);
        },
        onChange() {
            const tracker_ids = this.convertToInts(this.select2_control.val()) || [];
            const trackers = tracker_ids.map((id) => this.findTrackerById(id));
            this.$emit("change", trackers);
        },
    },
};
</script>
