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
    <div class="empty-page document-folder-with-error">
        <div class="empty-page-illustration">
            <permission-error-svg />
        </div>
        <div class="empty-page-text-with-small-text">
            <translate>This is a private folder.</translate>
            <div class="empty-page-small-text" v-translate>
                You may only access documents you are granted read permission on.
            </div>
        </div>
        <a href="/my/" class="tlp-button-primary tlp-button-large">
            <i class="fa fa-reply tlp-button-icon"></i>
            <translate>Go to my home page</translate>
        </a>
    </div>
</template>

<script>
import PermissionErrorSvg from "../../svg/error/PermissionErrorSvg.vue";

export default {
    name: "PermissionError",
    components: { PermissionErrorSvg },
};
</script>
