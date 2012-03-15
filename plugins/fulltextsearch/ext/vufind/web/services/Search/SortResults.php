<?php
/**
 *
 * Copyright (C) Villanova University 2010
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once 'Action.php';

/* This action only exists to allow sorting behavior to work when Javascripti s
 * disabled.  Normally, Javascript reads the new sort URL and redirects accordingly.
 * If Javascript is unavailable, the user has to submit the sort URL to this page,
 * which will do a server-side redirect to achieve the same effect.
 */
class SortResults extends Action {
    function launch()
    {
        if (isset($_REQUEST['sort'])) {
            header('Location: ' . $_REQUEST['sort']);
            die();
        }
    }
}

?>
