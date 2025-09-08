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

use Tuleap\Tracker\FormElement\Field\Files\FilesField;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFileFullRepresentation;

/**
 * Manage values in changeset for files fields
 */
class Tracker_Artifact_ChangesetValue_File extends Tracker_Artifact_ChangesetValue implements Countable, ArrayAccess, Iterator // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    /**
     * @var Tracker_FileInfo[]
     */
    protected array $files;

    public function __construct($id, Tracker_Artifact_Changeset $changeset, $field, $has_changed, $files)
    {
        parent::__construct($id, $changeset, $field, $has_changed);
        $this->files = $files;
    }

    /**
     * @return mixed
     */
    #[\Override]
    public function accept(Tracker_Artifact_ChangesetValueVisitor $visitor)
    {
        return $visitor->visitFile($this);
    }

    /**
     * spl\Countable
     *
     * @return int the number of files
     */
    #[\Override]
    public function count(): int
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
    #[\Override]
    public function offsetGet($offset): mixed
    {
        return $this->files[$offset];
    }

    /**
     * spl\ArrayAccess
     *
     * @param int   $offset to modify
     * @param mixed $value  new value
     */
    #[\Override]
    public function offsetSet($offset, $value): void
    {
        $this->files[$offset] = $value;
    }

    /**
     * spl\ArrayAccess
     *
     * @param int $offset to check
     */
    #[\Override]
    public function offsetExists($offset): bool
    {
        return isset($this->files[$offset]);
    }

    /**
     * spl\ArrayAccess
     *
     * @param int $offset to delete
     *
     */
    #[\Override]
    public function offsetUnset($offset): void
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
    #[\Override]
    public function current(): mixed
    {
        return $this->files[$this->index];
    }

    #[\Override]
    public function key(): int
    {
        return $this->index;
    }

    /**
     * spl\Iterator
     *
     * Jump to the next Tracker_FileInfo
     */
    #[\Override]
    public function next(): void
    {
        $this->index++;
    }

    /**
     * spl\Iterator
     *
     * Reset the pointer to the start of the collection
     */
    #[\Override]
    public function rewind(): void
    {
        $this->index = 0;
    }

    #[\Override]
    public function valid(): bool
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

    #[\Override]
    public function getRESTValue(PFUser $user)
    {
        return $this->getFullRESTValue($user);
    }

    #[\Override]
    public function getFullRESTValue(PFUser $user)
    {
        assert($this->field instanceof FilesField);
        $values = [];
        foreach ($this->getFiles() as $file_info) {
            $values[] = $file_info->getRESTValue();
        }

        return ArtifactFieldValueFileFullRepresentation::fromValues($this->field, $values);
    }

    /**
     * Returns the value of this changeset value
     *
     * @return mixed The value of this artifact changeset value
     */
    #[\Override]
    public function getValue()
    {
        // TODO : implement
        return false;
    }

    /**
     * @return false|string
     */
    #[\Override]
    public function mailDiff(
        $changeset_value,
        $artifact_id,
        $changeset_id,
        $ignore_perms,
        $format = 'html',
        ?PFUser $user = null,
    ) {
        return $this->formatDiff($changeset_value, $format, true);
    }

    /**
     * Returns a diff between this changeset value and the one passed in param
     *
     * @return string|false The difference between another $changeset_value, false if no differneces
     */
    #[\Override]
    public function diff($changeset_value, $format = 'html', ?PFUser $user = null, $ignore_perms = false)
    {
        return $this->formatDiff($changeset_value, $format, false);
    }

    private function formatDiff($changeset_value, $format, $is_for_mail)
    {
        if ($this->files !== $changeset_value->getFiles()) {
            $result  = '';
            $removed = [];
            foreach (array_diff($changeset_value->getFiles(), $this->files) as $fi) {
                $removed[] = $fi->getFilename();
            }
            if ($removed = implode(', ', $removed)) {
                $purifier = Codendi_HTMLPurifier::instance();
                $result  .= $purifier->purify($removed) . ' ' . dgettext('tuleap-tracker', 'removed');
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
    #[\Override]
    public function nodiff($format = 'html')
    {
        if (empty($this->files)) {
            return '';
        }

        return $this->fetchAddedFiles($this->files, $format, false);
    }

    private function fetchAddedFiles(array $files, $format, bool $is_for_mail): string
    {
        $artifact = $this->changeset->getArtifact();

        $still_existing_files_ids = [];

        if ($artifact->getLastChangeset()->getValue($this->field)) {
            foreach ($artifact->getLastChangeset()->getValue($this->field)->getFiles() as $file) {
                $still_existing_files_ids[$file->getId()] = true;
            }
        }

        $added    = [];
        $previews = [];
        $this->extractAddedAndPreviewsFromFiles($files, $format, $still_existing_files_ids, $added, $previews);

        $result = '';
        if ($added) {
            $result .= implode(', ', $added) . ' ' . dgettext('tuleap-tracker', 'added');
        }

        if ($previews && ! $is_for_mail) {
            $result .= '<div>' . $this->field->fetchAllAttachment(
                $artifact->getId(),
                $previews,
                true,
                [],
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
        &$previews,
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
        if (isset($still_existing_files_ids[$file->getId()])) {
            $added[] = '<a href="' . \Tuleap\ServerHostname::HTTPSUrl() . $purifier->purify($this->field->getFileHTMLUrl($file)) . '">' .
                $purifier->purify($file->getFilename())
                . '</a>';

            if ($file->isImage()) {
                $previews[] = $file;
            }
        } else {
            $reason  = dgettext('tuleap-tracker', 'This file has been removed meantime.');
            $added[] = '<s title="' . $purifier->purify($reason) . '">' .
                $purifier->purify($file->getFilename())
                . '</s>';
        }
    }
}
