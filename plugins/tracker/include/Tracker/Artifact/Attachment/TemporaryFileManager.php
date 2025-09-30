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

use Psl\Encoding\Exception\ExceptionInterface;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Tracker\Artifact\Attachment\InvalidBase64ContentChunkException;
use Tuleap\Tracker\Artifact\Attachment\QuotaExceededException;
use Tuleap\Tracker\Artifact\Attachment\PaginatedTemporaryFiles;
use Tuleap\User\RetrieveUserById;

/**
 * Manage temporary uploaded files
 */
class Tracker_Artifact_Attachment_TemporaryFileManager // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    public const TEMP_FILE_PREFIX = 'rest_attachement_temp_';

    /**
     * @var Tracker_Artifact_Attachment_TemporaryFileManagerDao
     */
    private $dao;

    /**
     * @var int
     */
    private $retention_delay_in_days;

    private RetrieveUserById $user_manager;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;

    public function __construct(
        RetrieveUserById $user_manager,
        Tracker_Artifact_Attachment_TemporaryFileManagerDao $dao,
        $retention_delay,
        DBTransactionExecutor $transaction_executor,
    ) {
        $this->dao                     = $dao;
        $this->retention_delay_in_days = $retention_delay;
        $this->user_manager            = $user_manager;
        $this->transaction_executor    = $transaction_executor;
    }

    public function purgeOldTemporaryFiles()
    {
        $timestamp = $_SERVER['REQUEST_TIME'] - $this->retention_delay_in_days * 3600 * 24;

        $old_files = $this->dao
            ->searchTemporaryFilesOlderThan($timestamp)
            ->instanciateWith([$this, 'getInstanceFromRow']);

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
     * @psalm-taint-escape shell
     * @psalm-taint-escape text
     * @psalm-return non-empty-string
     */
    public function getPath(PFUser $user, $attachment_name): string
    {
        $attachment_name = (string) $attachment_name;
        if (strpos($attachment_name, DIRECTORY_SEPARATOR) !== false) {
            throw new \RuntimeException('$attachment_name is not expected to contain a directory separator, got ' . $attachment_name);
        }
        $path = ForgeConfig::get('codendi_cache_dir') . DIRECTORY_SEPARATOR . $this->getUserTemporaryFilePrefix($user) . $attachment_name;
        assert($path !== '');
        return $path;
    }

    /**
     * Provision a new temporary file for user if possible and return it's UUID
     *
     * @return String
     */
    private function getUniqueFileName(PFUser $user)
    {
        $prefix    = $this->getUserTemporaryFilePrefix($user);
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
        $this->checkThatChunkSizeIsNotOverTheQuota($user, '');
        $tempname = $this->getUniqueFileName($user);

        $temporary_file = $this->transaction_executor->execute(function () use ($user, $name, $description, $mimetype, $tempname) {
            $timestamp = $_SERVER['REQUEST_TIME'];
            $id        = $this->dao->create($user->getId(), $name, $description, $mimetype, $timestamp, $tempname);
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
     * @throws Tracker_Artifact_Attachment_InvalidPathException
     * @throws Tracker_Artifact_Attachment_InvalidOffsetException
     * @throws Tracker_Artifact_Attachment_ChunkTooBigException
     * @throws Tuleap\Tracker\Artifact\Attachment\QuotaExceededException
     */
    public function appendChunk(string $content, Tracker_Artifact_Attachment_TemporaryFile $file, int $offset): bool
    {
        $current_offset = $file->getCurrentChunkOffset();

        if ($current_offset + 1 !== $offset) {
            throw new Tracker_Artifact_Attachment_InvalidOffsetException();
        }

        $user = $this->user_manager->getUserById($file->getCreatorId());
        if (! $this->exists($user, $file->getTemporaryName())) {
            throw new Tracker_Artifact_Attachment_InvalidPathException('Invalid temporary file path');
        }

        try {
            $decoded_content = \Psl\Encoding\Base64\decode($content);
        } catch (ExceptionInterface $ex) {
            throw new InvalidBase64ContentChunkException($ex->getMessage(), $ex->getCode(), $ex);
        }

        $this->validateChunkSize($user, $decoded_content);

        $path = $this->getPath($user, $file->getTemporaryName());
        \Psl\File\write($path, $decoded_content, \Psl\File\WriteMode::Append);

        $size = \Psl\Filesystem\file_size($path);
        $file->setSize($size);

        return $this->dao->updateFileInfo($file->getId(), $offset, $_SERVER['REQUEST_TIME'], $size);
    }

    public function getPaginatedUserTemporaryFiles(PFUser $user, $offset, $limit): PaginatedTemporaryFiles
    {
        $files = $this->dao
            ->searchPaginatedUserTemporaryFiles($user->getId(), $offset, $limit)
            ->instanciateWith([$this, 'getInstanceFromRow']);

        return new PaginatedTemporaryFiles($files, $this->dao->foundRows());
    }

    public function getDiskUsage(PFUser $user): int
    {
        $size = 0;
        foreach (glob($this->getPath($user, '*')) as $file) {
            $size += \Psl\Filesystem\file_size($file);
        }

        return $size;
    }

    /**
     * @throws Tuleap\Tracker\Artifact\Attachment\QuotaExceededException
     */
    private function checkThatChunkSizeIsNotOverTheQuota(PFUser $user, string $decoded_content): void
    {
        $chunk_size = strlen($decoded_content);
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
     * @throws Tuleap\Tracker\Artifact\Attachment\QuotaExceededException
     */
    private function validateChunkSize(PFUser $user, string $decoded_content): void
    {
        $chunk_size = strlen($decoded_content);

        if ($chunk_size > $this->getMaximumChunkSize()) {
            throw new Tracker_Artifact_Attachment_ChunkTooBigException();
        }

        $this->checkThatChunkSizeIsNotOverTheQuota($user, $decoded_content);
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
