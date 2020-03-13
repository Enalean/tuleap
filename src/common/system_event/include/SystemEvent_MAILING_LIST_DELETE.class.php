<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 *
 *
 */


/**
* System Event classes
*
*/
class SystemEvent_MAILING_LIST_DELETE extends SystemEvent
{

    /**
     * Verbalize the parameters so they are readable and much user friendly in
     * notifications
     *
     * @param bool $with_link true if you want links to entities. The returned
     * string will be html instead of plain/text
     *
     * @return string
     */
    public function verbalizeParameters($with_link)
    {
        $txt = '';
        $txt .= 'mailing list: #' . $this->getIdFromParam($this->parameters);
        return $txt;
    }

    /**
     * Process stored event
     */
    public function process()
    {
        // Check parameters
        $group_list_id = $this->getIdFromParam($this->parameters);

        if ($group_list_id == 0) {
            return $this->setErrorBadParam();
        }

        if (!Backend::instance('MailingList')->deleteList($group_list_id)) {
            $this->error("Could not delete mailing list $group_list_id");
            return false;
        }

        // Need to add list aliases
        Backend::instance('Aliases')->setNeedUpdateMailAliases();

        $this->done();
        return true;
    }
}
