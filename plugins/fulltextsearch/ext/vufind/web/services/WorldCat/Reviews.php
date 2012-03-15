<?php
/**
 *
 * Copyright (C) Andrew Nagy 2009.
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
 
require_once 'Record.php';
require_once 'sys/Reviews.php';

class Reviews extends Record
{
    function launch() 
    {
        global $interface;

        if (!$interface->is_cached($this->cacheId)) {
            $rev = new ExternalReviews($this->isbn);
            $reviews = $rev->fetch();
            if ($reviews) {
                $interface->assign('reviews', $reviews);
            }
            $interface->assign('subTemplate', '../Record/view-reviews.tpl');
            $interface->setTemplate('view.tpl');
        }

        // Display Page
        $interface->display('layout.tpl', $this->cacheId);
    }
}
?>