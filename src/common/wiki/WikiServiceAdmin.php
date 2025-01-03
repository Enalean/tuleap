<?php
/*
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
 * Copyright 2005, STMicroelectronics
 *
 * Originally written by Manuel Vacelet
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class WikiServiceAdmin extends Controler
{
  /* private Wiki*/ public $wiki;

    public function __construct($id)
    {
        global $LANG, $is_wiki_page;

      //used so the search box will add the necessary element to the pop-up box
        $is_wiki_page = 1;

        $this->gid = (int) $id;

        if (empty($this->gid)) {
            exit_no_group();
        }

        if (! user_ismember($this->gid, 'W2') && ! user_ismember($this->gid, 'A')) {
            exit_permission_denied();
        }

        $this->wiki = new Wiki($this->gid);

      // If Wiki for project doesn't exist, propose creation...
        if (! $this->wiki->exist()) {
            /**
             * @psalm-taint-escape header
             */
            $header = 'Location: /wiki/index.php?group_id=' . urlencode($this->gid) . '&view=install';
            header($header);
            exit();
        }

      // Set language for phpWiki
        if ($this->wiki->getLanguage_id()) {
            define('DEFAULT_LANGUAGE', $this->wiki->getLanguage_id());
            $LANG = $this->wiki->getLanguage_id();
        }
    }

    public function request()
    {
      // Default behaviour: display default view:
        $this->view = 'main';

        if (! empty($_REQUEST['view'])) {
            $this->view = $_REQUEST['view'];
        }

        if (! empty($_REQUEST['action'])) {
            $this->action = $_REQUEST['action'];
        }
    }
}
