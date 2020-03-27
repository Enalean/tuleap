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
    <img
        class="old-color-picker-preview"
        v-bind:src="previewImage"
        v-bind:style="{ 'background-color': color }"
        v-on:click="setTransparent"
        v-bind:title="color"
        alt="/themes/FlamingParrot/images/blank16x16.png"
    />
</template>

<script>
export default {
    name: "OldColorPickerPreview",
    props: {
        color: String,
    },
    computed: {
        previewImage() {
            const base_url = "/themes/FlamingParrot/images/";

            if (this.color.length) {
                return base_url + "blank16x16.png";
            }

            return base_url + "ic/layer-transparent.png";
        },
    },
    methods: {
        setTransparent() {
            if (this.color.length) {
                return;
            }

            this.$emit("color-update");
        },
    },
};
</script>
