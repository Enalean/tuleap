<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
    <p class="tlp-text-info" v-if="item_title !== null">
        <i class="fa fa-info-circle document-clipboard-content-information-icon"></i>
        <translate v-bind:translate-params="{ title: item_title }">
            You are currently copying "%{ title }". You can paste it in a folder allowed to write into using the folder action dropdown.
        </translate>
    </p>
</template>
<script>
import { mapState } from "vuex";

export default {
    name: "ClipboardContentInformation",
    computed: {
        ...mapState("clipboard", ["item_title"])
    }
};
</script>
