<!--
  - Copyright (c) Enalean, 2018 - 2018. All Rights Reserved.
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
    <button
        type="button"
        v-on:click="showNewDocumentModal()"
    >
        <i class="fa fa-plus tlp-button-icon"></i>
        <translate>New document</translate>
    </button>
</template>

<script>
export default {
    methods: {
        showNewDocumentModal() {
            document.dispatchEvent(new CustomEvent("show-new-document-modal"));
        }
    }
};
</script>
