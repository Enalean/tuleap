<?php
/**
 * Copyright (c) Enalean, 2014 — Present. All Rights Reserved.
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

use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Tracker\Artifact\Attachment\QuotaExceededException;
use Tuleap\Tracker\Artifact\Attachment\PaginatedTemporaryFiles;

/**
 * Manage temporary uploaded files
 */
class Tracker_Artifact_Attachment_TemporaryFileManager
{

    public const TEMP_FILE_PREFIX = 'rest_attachement_temp_';

    /**
     * @var Tracker_Artifact_Attachment_TemporaryFileManagerDao
     */
    private $dao;

    /**
     * @var System_Command
     */
    private $system;

    /**
     * @var int
     */
    private $retention_delay_in_days;

    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;

    public function __construct(
        UserManager $user_manager,
        Tracker_Artifact_Attachment_TemporaryFileManagerDao $dao,
        System_Command $system,
        $retention_delay,
        DBTransactionExecutor $transaction_executor
    ) {
        $this->dao                     = $dao;
        $this->system                  = $system;
        $this->retention_delay_in_days = $retention_delay;
        $this->user_manager            = $user_manager;
        $this->transaction_executor    = $transaction_executor;
    }

    public function purgeOldTemporaryFiles()
    {
        $timestamp = $_SERVER['REQUEST_TIME'] - $this->retention_delay_in_days * 3600 * 24;

        $old_files = $this->dao
            ->searchTemporaryFilesOlderThan($timestamp)
            ->instanciateWith(array($this, 'getInstanceFromRow'));

        foreach ($old_files as $file) {
            $this->removeTemporaryFile($file);
        }
    }

    /**
     * Does the temporary file exists on filesystem
     *
     * @return bool
     */
    public function exists(PFUser $user, $attachment_name)
    {
        return file_exists($this->getPath($user, $attachment_name));
    }

    /**
     * Return full path to the file on filesystem
     *
     * @return String
     */
    public function getPath(PFUser $user, $attachment_name)
    {
        return ForgeConfig::get('codendi_cache_dir') . DIRECTORY_SEPARATOR . $this->getUserTemporaryFilePrefix($user) . $attachment_name;
    }

    /**
     * Provision a new temporary file for user if possible and return it's UUID
     *
     * @return String
     */
    private function getUniqueFileName(PFUser $user)
    {
        $prefix = $this->getUserTemporaryFilePrefix($user);
        $file_path = tempnam(ForgeConfig::get('codendi_cache_dir'), $prefix);
        return substr(basename($file_path), strlen($prefix));
    }

    /**
     * @throws Tracker_Artifact_Attachment_CannotCreateException
     * @throws Tuleap\Tracker\Artifact\Attachment\QuotaExceededException
     *
     * @return \Tracker_Artifact_Attachment_TemporaryFile
     */
    public function save(PFUser $user, $name, $description, $mimetype)
    {
        $chunk_size = 0;
        $this->checkThatChunkSizeIsNotOverTheQuota($user, $chunk_size);

        $tempname  = $this->getUniqueFileName($user);

        $temporary_file = $this->transaction_executor->execute(function () use ($user, $name, $description, $mimetype, $tempname) {
            $timestamp = $_SERVER['REQUEST_TIME'];
            $id = $this->dao->create($user->getId(), $name, $description, $mimetype, $timestamp, $tempname);
            if (! $id) {
                throw new Tracker_Artifact_Attachment_CannotCreateException();
            }

            return new Tracker_Artifact_Attachment_TemporaryFile(
                $id,
                $name,
                $tempname,
                $description,
                0,
                $user->getId(),
                0,
                $mimetype
            );
        });

        return $temporary_file;
    }

    /**
     * Returns encoded content chunk of file
     *
     * @param int $offset Where to start reading
     * @param int $size   How much to read
     *
     * @return string Base64 encoded content
     *
     * @throws Tracker_Artifact_Attachment_FileNotFoundException
     */
    public function getTemporaryFileChunk(Tracker_Artifact_Attachment_TemporaryFile $file, $offset, $size)
    {
        $temporary_name = $file->getTemporaryName();

        $user = $this->user_manager->getUserById($file->getCreatorId());
        if ($this->exists($user, $temporary_name)) {
            return base64_encode(file_get_contents($this->getPath($user, $temporary_name), false, null, $offset, $size));
        }

        throw new Tracker_Artifact_Attachment_FileNotFoundException();
    }

    /**
     * Append some content (base64 encoded) to the file
     *
     * @param String $content
     * @param int $offset
     *
     * @return bool
     * @throws Tracker_Artifact_Attachment_InvalidPathException
     * @throws Tracker_Artifact_Attachment_InvalidOffsetException
     */
    public function appendChunk($content, Tracker_Artifact_Attachment_TemporaryFile $file, $offset)
    {
        $current_offset = $file->getCurrentChunkOffset();

        if ($current_offset + 1 !== (int) $offset) {
            throw new Tracker_Artifact_Attachment_InvalidOffsetException();
        }

        $user = $this->user_manager->getUserById($file->getCreatorId());
        if (! $this->exists($user, $file->getTemporaryName())) {
            throw new Tracker_Artifact_Attachment_InvalidPathException('Invalid temporary file path');
        }

        $this->checkThatChunkSizeIsNotOverTheQuota($user, $content);
        $path = $this->getPath($user, $file->getTemporaryName());
        $bytes_written = file_put_contents($path, base64_decode($content), FILE_APPEND);

        $size = (int) implode('', $this->system->exec('stat -c %s ' . escapeshellarg($path)));
        $file->setSize($size);

        return $bytes_written && $this->dao->updateFileInfo($file->getId(), $offset, $_SERVER['REQUEST_TIME'], $size);
    }

    public function getPaginatedUserTemporaryFiles(PFUser $user, $offset, $limit): PaginatedTemporaryFiles
    {
        $files = $this->dao
            ->searchPaginatedUserTemporaryFiles($user->getId(), $offset, $limit)
            ->instanciateWith(array($this, 'getInstanceFromRow'));

        return new PaginatedTemporaryFiles($files, $this->dao->foundRows());
    }

    /**
     * @return int
     */
    public function getDiskUsage(PFUser $user)
    {
        $size = 0;
        foreach (glob($this->getPath($user, '*')) as $file) {
            $size += (int) implode('', $this->system->exec('stat -c %s ' . escapeshellarg($file)));
        }

        return $size;
    }

    /**
     * @throws Tuleap\Tracker\Artifact\Attachment\QuotaExceededException
     */
    private function checkThatChunkSizeIsNotOverTheQuota(PFUser $user, $content)
    {
        $chunk_size = strlen(base64_decode($content));
        if ($this->getDiskUsage($user) + $chunk_size > $this->getQuota()) {
            throw new QuotaExceededException();
        }
    }

    private function getUserTemporaryFilePrefix(PFUser $user)
    {
        return self::TEMP_FILE_PREFIX . $user->getId() . '_';
    }

    /**
     * @throws Tracker_Artifact_Attachment_ChunkTooBigException
     */
    public function validateChunkSize(PFUser $user, $content)
    {
        $chunk_size = strlen(base64_decode($content));

        if ($chunk_size > $this->getMaximumChunkSize()) {
            throw new Tracker_Artifact_Attachment_ChunkTooBigException();
        }

        $this->checkThatChunkSizeIsNotOverTheQuota($user, $content);
    }

    /**
     * Max chunk size : 1 Mo = 1048576 bytes
     */
    public function getMaximumChunkSize()
    {
        return 1048576;
    }

    /**
     * Max chunk size : 64 Mo = 67108864 bytes
     */
    public function getQuota()
    {
        return ForgeConfig::get('sys_max_size_upload');
    }

    /**
     * @return \Tracker_Artifact_Attachment_TemporaryFile
     * @throws Tracker_Artifact_Attachment_FileNotFoundException
     */
    public function getFile($id)
    {
        $row = $this->dao->getTemporaryFile($id);

        if (! $row) {
            throw new Tracker_Artifact_Attachment_FileNotFoundException();
        }

        return $this->getInstanceFromRow($row);
    }

    public function getFileByTemporaryName($temporary_name)
    {
        $row = $this->dao->getTemporaryFileByTemporaryName($temporary_name);

        if (! $row) {
            return;
        }

        return $this->getInstanceFromRow($row);
    }

    public function isFileIdTemporary($id)
    {
        return $this->dao->doesFileExist($id);
    }

    public function removeTemporaryFile(Tracker_Artifact_Attachment_TemporaryFile $file)
    {
        $this->removeTemporaryFileInDB($file->getId());
        $this->removeTemporaryFileFomFileSystem($file);
    }

    private function removeTemporaryFileInDB($id)
    {
        $this->dao->delete($id);
    }

    public function removeTemporaryFileInDBByTemporaryName($temporary_name)
    {
        $this->dao->deleteByTemporaryName($temporary_name);
    }

    private function removeTemporaryFileFomFileSystem(Tracker_Artifact_Attachment_TemporaryFile $temporary_file)
    {
        $user = $this->user_manager->getUserById($temporary_file->getCreatorId());

        $temporary_file_name = $temporary_file->getTemporaryName();
        $temporary_file_path = $this->getPath($user, $temporary_file_name);

        if ($this->exists($user, $temporary_file_name)) {
            unlink($temporary_file_path);
        }
    }

    public function getInstanceFromRow($row)
    {
        return new Tracker_Artifact_Attachment_TemporaryFile(
            $row['id'],
            $row['filename'],
            $row['tempname'],
            $row['description'],
            $row['offset'],
            $row['submitted_by'],
            $row['filesize'],
            $row['filetype']
        );
    }
}
