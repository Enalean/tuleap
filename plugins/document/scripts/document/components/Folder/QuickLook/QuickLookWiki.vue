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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -->

<template>
    <div class="document-quick-look-wiki-action">
        <button type="button" class="tlp-button-primary tlp-button-small document-quick-look-action-button-margin"
                v-on:click="wiki_page_redirect">
            <i class="fa fa-long-arrow-right tlp-button-icon"></i>
            <translate>Go to the wiki page</translate>
        </button>
    </div>
</template>

<script>
export default {
    name: "QuickLookWiki",
    props: {
        item: Object
    },
    methods: {
        wiki_page_redirect() {
            window.location.assign(encodeURI(this.item.wiki_properties.html_url));
        }
    }
};
</script>
