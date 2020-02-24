<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * Represents the revison info for a given svn path
 */
class SVN_RevisionPathInfo
{
    private $path;
    private $author_id;
    private $timestamp;
    private $commit_message;

    public function __construct($path, $author_id, $timestamp, $commit_message)
    {
        $this->path             = $path;
        $this->author_id        = $author_id;
        $this->timestamp        = $timestamp;
        $this->commit_message   = $commit_message;
    }

    public function exportToSoap()
    {
        return array(
            'path'      => $this->path,
            'author'    => (int) $this->author_id,
            'timestamp' => (int) $this->timestamp,
            'message'   => $this->commit_message,
        );
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }
}
