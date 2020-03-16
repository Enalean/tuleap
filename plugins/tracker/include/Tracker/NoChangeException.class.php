<?php
/**
 * Copyright (c) Enalean, 2012. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */
class Tracker_NoChangeException extends Tracker_Exception
{

    /**
     *
     * @param int $artifact_id
     * @param string $artifact_xref cross reference text @see Tracker_Artifact::getXRef()
     * @param string $message
     * @param int $code
     * @param Exception $previous
     */
    public function __construct($artifact_id, $artifact_xref, $message = null, $code = null)
    {
        if ($message === null) {
            $art_link = '<a class="direct-link-to-artifact" href="' . TRACKER_BASE_URL . '/?aid=' . $artifact_id . '">' .
                     $artifact_xref . '</a>';
            $message = $GLOBALS['Language']->getText('plugin_tracker_artifact', 'no_changes', array($art_link));
        }

        parent::__construct($message, $code);
    }
}
