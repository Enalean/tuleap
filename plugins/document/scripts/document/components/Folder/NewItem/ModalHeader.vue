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
  -
  -->

<template>
    <div class="tlp-modal-header">
        <h1 class="tlp-modal-title" id="document-new-item-modal">
            <i class="fa fa-plus tlp-modal-title-icon"></i>
            <translate>New document</translate>
        </h1>
        <div class="tlp-modal-close" data-dismiss="modal" v-bind:aria-label="close">
            &times;
        </div>
    </div>
</template>
<script>
export default {
    name: "ModalHeader",
    computed: {
        close() {
            return this.$gettext("Close");
        }
    }
};
</script>
