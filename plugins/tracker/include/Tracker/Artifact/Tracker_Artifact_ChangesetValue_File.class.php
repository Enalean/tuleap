<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFileFullRepresentation;

/**
 * Manage values in changeset for files fields
 */
class Tracker_Artifact_ChangesetValue_File extends Tracker_Artifact_ChangesetValue implements Countable, ArrayAccess, Iterator
{

    /**
     * @var array of Tracker_FileInfo
     */
    protected $files;

    public function __construct($id, Tracker_Artifact_Changeset $changeset, $field, $has_changed, $files)
    {
        parent::__construct($id, $changeset, $field, $has_changed);
        $this->files = $files;
    }

    /**
     * @return mixed
     */
    public function accept(Tracker_Artifact_ChangesetValueVisitor $visitor)
    {
        return $visitor->visitFile($this);
    }

    /**
     * spl\Countable
     *
     * @return int the number of files
     */
    public function count()
    {
        return count($this->files);
    }

    /**
     * spl\ArrayAccess
     *
     * @param int|string $offset to retrieve
     *
     * @return mixed value at given offset
     */
    public function offsetGet($offset)
    {
        return $this->files[$offset];
    }

    /**
     * spl\ArrayAccess
     *
     * @param int   $offset to modify
     * @param mixed $value  new value
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->files[$offset] = $value;
    }

    /**
     * spl\ArrayAccess
     *
     * @param int $offset to check
     *
     * @return bool wether the offset exists
     */
    public function offsetExists($offset)
    {
        return isset($this->files[$offset]);
    }

    /**
     * spl\ArrayAccess
     *
     * @param int $offset to delete
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->files[$offset]);
    }

    /**
     * spl\Iterator
     *
     * The internal pointer to traverse the collection
     * @var int
     */
    protected $index;

    /**
     * spl\Iterator
     *
     * @return Tracker_FileInfo the current one
     */
    public function current()
    {
        return $this->files[$this->index];
    }

    /**
     * spl\Iterator
     *
     * @return int the current index
     */
    public function key()
    {
        return $this->index;
    }

    /**
     * spl\Iterator
     *
     * Jump to the next Tracker_FileInfo
     *
     * @return void
     */
    public function next()
    {
        $this->index++;
    }

    /**
     * spl\Iterator
     *
     * Reset the pointer to the start of the collection
     *
     * @return Tracker_FileInfo the current one
     */
    public function rewind()
    {
        $this->index = 0;
    }

    /**
     * spl\Iterator
     *
     * @return bool true if the current pointer is valid
     */
    public function valid()
    {
        return isset($this->files[$this->index]);
    }

    /**
     * Get the files infos
     *
     * @return Tracker_FileInfo[]
     */
    public function getFiles()
    {
        return $this->files;
    }

    public function getRESTValue(PFUser $user)
    {
        return $this->getFullRESTValue($user);
    }

    public function getFullRESTValue(PFUser $user)
    {
        $values = array();
        foreach ($this->getFiles() as $file_info) {
            $values[] = $file_info->getRESTValue();
        }
        $field_value_file_representation = new ArtifactFieldValueFileFullRepresentation();
        $field_value_file_representation->build(
            $this->field->getId(),
            Tracker_FormElementFactory::instance()->getType($this->field),
            $this->field->getLabel(),
            $values
        );
        return $field_value_file_representation;
    }

    /**
     * Returns the value of this changeset value
     *
     * @return mixed The value of this artifact changeset value
     */
    public function getValue()
    {
        // TODO : implement
        return false;
    }

    /**
     * @return false|string
     */
    public function mailDiff(
        $changeset_value,
        $artifact_id,
        $changeset_id,
        $ignore_perms,
        $format = 'html',
        ?PFUser $user = null
    ) {
        return $this->formatDiff($changeset_value, $format, true);
    }

    /**
     * Returns a diff between this changeset value and the one passed in param
     *
     * @return string|false The difference between another $changeset_value, false if no differneces
     */
    public function diff($changeset_value, $format = 'html', ?PFUser $user = null, $ignore_perms = false)
    {
        return $this->formatDiff($changeset_value, $format, false);
    }

    private function formatDiff($changeset_value, $format, $is_for_mail)
    {
        if ($this->files !== $changeset_value->getFiles()) {
            $result = '';
            $removed = array();
            foreach (array_diff($changeset_value->getFiles(), $this->files) as $fi) {
                $removed[] = $fi->getFilename();
            }
            if ($removed = implode(', ', $removed)) {
                $result .= $removed . ' ' . $GLOBALS['Language']->getText('plugin_tracker_artifact', 'removed');
            }

            $added = $this->fetchAddedFiles(array_diff($this->files, $changeset_value->getFiles()), $format, $is_for_mail);
            if ($added && $result) {
                $result .= $format === 'html' ? '; ' : PHP_EOL;
            }
            $result .= $added;

            return $result;
        }
        return false;
    }

     /**
     * Returns the "set to" for field added later
     *
     * @return string The sentence to add in changeset
     */
    public function nodiff($format = 'html')
    {
        if (empty($this->files)) {
            return '';
        }

        return $this->fetchAddedFiles($this->files, $format, false);
    }

    private function fetchAddedFiles(array $files, $format, $is_for_mail)
    {
        $artifact = $this->changeset->getArtifact();

        $still_existing_files_ids = array();

        if ($artifact->getLastChangeset()->getValue($this->field)) {
            foreach ($artifact->getLastChangeset()->getValue($this->field)->getFiles() as $file) {
                $still_existing_files_ids[$file->getId()] = true;
            }
        }

        $added    = array();
        $previews = array();
        $this->extractAddedAndPreviewsFromFiles($files, $format, $still_existing_files_ids, $added, $previews);

        $result   = '';
        if ($added) {
            $result .= implode(', ', $added) . ' ' . $GLOBALS['Language']->getText('plugin_tracker_artifact', 'added');
        }

        if ($previews && ! $is_for_mail) {
            $result .= '<div>' . $this->field->fetchAllAttachment(
                $artifact->getId(),
                $previews,
                true,
                array(),
                true,
                $this->changeset->getId()
            ) . '</div>';
        }

        return $result;
    }

    private function extractAddedAndPreviewsFromFiles(
        array $files,
        $format,
        $still_existing_files_ids,
        &$added,
        &$previews
    ) {
        foreach ($files as $file) {
            \assert($file instanceof Tracker_FileInfo);
            if ($format === 'html') {
                $this->addFileForHTMLFormat($still_existing_files_ids, $added, $previews, $file);
            } else {
                $added[] = $file->getFilename();
            }
        }
    }

    private function addFileForHTMLFormat($still_existing_files_ids, &$added, &$previews, $file)
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $request  = HTTPRequest::instance();
        if (isset($still_existing_files_ids[$file->getId()])) {
            $added[] = '<a href="' . $request->getServerUrl() . $purifier->purify($this->field->getFileHTMLUrl($file)) . '">' .
                $purifier->purify($file->getFilename())
                . '</a>';

            if ($file->isImage()) {
                $previews[] = $file;
            }
        } else {
            $reason  = $GLOBALS['Language']->getText('plugin_tracker', 'file_has_been_removed_meantime');
            $added[] = '<s title="' . $purifier->purify($reason) . '">' .
                $purifier->purify($file->getFilename())
                . '</s>';
        }
    }
}
