<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

use Symfony\Component\Process\Process;
use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;
use Tuleap\Event\Events\ArchiveDeletedItemEvent;
use Tuleap\Event\Events\ArchiveDeletedItemFileProvider;
use Tuleap\FRS\FRSPermissionManager;

class FRSFileFactory // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public const COMPUTE_MD5 = 0x0001;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var string[]
     */
    private $fileforge                         = ['sudo', __DIR__ . '/../../utils/fileforge.pl'];
    public ?FRSReleaseFactory $release_factory = null;

    public function __construct(?\Psr\Log\LoggerInterface $logger = null)
    {
        if ($logger === null) {
            $this->logger = new \Psr\Log\NullLogger();
        } else {
            $this->setLogger($logger);
        }
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = new WrapperLogger($logger, 'FRSFileFactory');
    }

    public function setFileForge(array $fileforge): void
    {
        $this->fileforge = $fileforge;
    }

    /**
     * @return FRSFile
     */
    public function &getFRSFileFromArray(&$array)
    {
        $frs_file = new FRSFile($array);
        return $frs_file;
    }

    /**
     * @return FRSFile | null
     */
    public function getFRSFileFromDb($file_id, $group_id = null)
    {
        $_id = (int) $file_id;
        $dao = $this->_getFRSFileDao();
        if ($group_id) {
            $_group_id = (int) $group_id;
            $dar       = $dao->searchInReleaseById($_id, $group_id);
        } else {
            $dar = $dao->searchById($_id);
        }

        $file = null;
        if (! $dar->isError() && $dar->valid()) {
            $data_array = $dar->current();
            $file       = self::getFRSFileFromArray($data_array);
        }
        return $file;
    }

    /**
     * get the files of the release.
     *
     * @param int $release_id the ID of the release the files belong to
     */
    public function &getFRSFilesFromDb($release_id)
    {
        $_id = (int) $release_id;
        $dao = $this->_getFRSFileDao();
        $dar = $dao->searchByReleaseId($_id);

        $files = [];
        if (! $dar->isError() && $dar->valid()) {
            while ($dar->valid()) {
                $data_array = $dar->current();
                $files[]    = self::getFRSFileFromArray($data_array);
                $dar->next();
            }
        }
        return $files;
    }

    public function getFRSFileInfoListFromDb($group_id, $file_id)
    {
        $_group_id = (int) $group_id;
        $_file_id  = (int) $file_id;
        $dao       = $this->_getFRSFileDao();

        $dar = $dao->searchInfoByGroupFileID($_group_id, $_file_id);

        if ($dar->isError()) {
            return;
        }

        if (! $dar->valid()) {
            return;
        }

        $file_info =  [];
        while ($dar->valid()) {
            $file_info[] = $dar->current();
            $dar->next();
        }
        return $file_info;
    }

    public function getFRSFileInfoListByReleaseFromDb($release_id)
    {
        $_release_id = (int) $release_id;
        $dao         = $this->_getFRSFileDao();

        $dar = $dao->searchInfoFileByReleaseID($_release_id);

        if ($dar->isError()) {
            return;
        }

        if (! $dar->valid()) {
            return;
        }

        $file_info =  [];
        while ($dar->valid()) {
            $file_info[] = $dar->current();
            $dar->next();
        }
        return $file_info;
    }

    public function isFileNameExist($file_name, $group_id)
    {
        $_id = (int) $group_id;
        $dao = $this->_getFRSFileDao();
        $dar = $dao->searchFileByName($file_name, $_id);

        if ($dar->isError()) {
            return;
        }

        return $dar->valid();
    }

    /**
     * Determine if there is already a file named $file_basename in the release $release_id for the project $group_id
     *
     * @param string $file_basename the file name (base, without directory) we want to check
     * @param int $release_id the ID of the release the file belongs to
     * @param int $group_id the ID of the project the file belongs to
     * @return bool true if a file named $file_basename already exists in the release $release_id, false otherwise
     */
    public function isFileBaseNameExists($file_basename, $release_id, $group_id)
    {
        $release   = $this->_getFRSReleaseFactory()->getFRSReleaseFromDb($release_id);
        $subdir    = $this->getUploadSubDirectory($release);
        $file_name = $subdir . '/' . $file_basename;
        return $this->isFileNameExist($file_name, $group_id);
    }

    /**
     * Determine if there is already a file named $basename in the release and marked to be restored
     * but not yet moved to its original path
     *
     * @param String $basename
     * @param int $release_id
     *
     * @return bool
     */
    public function isSameFileMarkedToBeRestored($basename, $release_id)
    {
        $dao      = $this->_getFRSFileDao();
        $release  = $this->_getFRSReleaseFactory()->getFRSReleaseFromDb($release_id, null, null, true);
        $subdir   = $this->getUploadSubDirectory($release);
        $filename = $subdir . '/' . $basename;
        return $dao->isMarkedToBeRestored($filename);
    }

    public $dao;

    public function &_getFRSFileDao()
    {
        if (! $this->dao) {
            $this->dao = new FRSFileDao(CodendiDataAccess::instance());
        }
        return $this->dao;
    }

    public function update($data_array)
    {
        $dao      = $this->_getFRSFileDao();
        $old_file = $this->getFRSFileFromDb($data_array['file_id']);

        if ($dao->updateFromArray($data_array)) {
            $file = $this->getFRSFileFromDb($data_array['file_id']);
            $this->_getEventManager()->processEvent(
                'frs_update_file',
                [
                    'group_id' => $file->getGroup()->getGroupId(),
                    'item_id'    => $data_array['file_id'],
                ]
            );

            if ($old_file->getFilePath() != $file->getFilePath()) {
                $release      = $this->_getFRSReleaseFactory()->getFRSReleaseFromDb($file->getReleaseID());
                $project_name = $release->getProject()->getUnixName(false);

                $this->moveFileToPath($file, $old_file->getFilePath(), $project_name);
            }

            return true;
        }
        return false;
    }

    private function moveFileToPath(FRSFile $file, $old_path, $project_name)
    {
        $project_path = ForgeConfig::get('ftp_frs_dir_prefix') . '/' . $project_name . '/';
        $file_id      = $file->getFileID();

        $manager = $this->_getSystemEventManager();
        $manager->addSystemEvent(
            SystemEvent_MOVE_FRS_FILE::NAME,
            [
                'project_path' => $project_path,
                'file_id'      => $file_id,
                'old_path'     => $old_path,
            ]
        );

        $GLOBALS['Response']->addFeedback(Feedback::INFO, $GLOBALS['Language']->getText('file_admin_editreleases', 'move_event'));
    }

    public function create($data_array)
    {
        $dao = $this->_getFRSFileDao();
        if ($id = $dao->createFromArray($data_array)) {
            $file = $this->getFRSFileFromDb($id);
            $um   = UserManager::instance();
            $user = $um->getCurrentUser();
            $this->_getEventManager()->processEvent(
                'frs_create_file',
                ['group_id' => $file->getGroup()->getGroupId(),
                    'item_id'    => $id,
                ]
            );
            return $id;
        }
        return false;
    }

    /**
     * Create a new file based on given objects
     *
     * Given a "transient" file object, physically move the file from it's landing zone to
     * it's release area and create the corresponding entry in the database.
     *
     * @param FRSFile    $file    File to create
     *
     * @return FRSFile
     */
    public function createFile(FRSFile $file, $extraFlags = self::COMPUTE_MD5)
    {
        $this->logger->debug('createFile start');
        $rule = new Rule_FRSFileName();
        if (! $rule->isValid($file->getFileName())) {
            throw new FRSFileIllegalNameException($file);
        }
        $rel = $file->getRelease();

        if ($this->isFileBaseNameExists($file->getFileName(), $rel->getReleaseID(), $rel->getGroupID())) {
            throw new FRSFileExistsException($file);
        }

        if ($this->isSameFileMarkedToBeRestored($file->getFileName(), $rel->getReleaseID())) {
            throw new FRSFileToBeRestoredException($file);
        }

        $checker = new FRSOngoingUploadChecker($file);
        EventManager::instance()->processEvent($checker);
        if ($checker->isFileBeingUploaded()) {
            throw new FRSFileOngoingUploadException($file);
        }

        clearstatcache();
        $filePath = $this->getSrcDir($rel->getProject()) . '/' . $file->getFileName();
        if (! file_exists($filePath)) {
            throw new FRSFileInvalidNameException($file);
        }

        if (0 != ($extraFlags & self::COMPUTE_MD5)) {
            $file->setComputedMd5(hash_file('md5', $filePath));
            if (! $this->compareMd5Checksums($file->getComputedMd5(), $file->getReferenceMd5())) {
                throw new FRSFileMD5SumException($file);
            }
        }

        $file->setFileSize(filesize($filePath));
        $file->setStatus('A');

        $now = time();
        if ($file->getReleaseTime() === null) {
            $file->setReleaseTime($now);
        }
        if ($file->getPostDate() === null) {
            $file->setPostDate($now);
        }

        $this->logger->debug('file ready to be moved');
        if ($this->moveFileForge($file)) {
            $this->logger->debug('file moved, create into the DB');
            $fileId = $this->create($file->toArray());
            $this->logger->debug('file ' . $fileId . ' created');
            if ($fileId) {
                $file->setFileID($fileId);
                $this->logger->debug('createFile completed');
                return $file;
            } else {
                throw new FRSFileDbException($file);
            }
        } else {
            throw new FRSFileForgeException($file);
        }
    }

    /**
     * Returns the names of the files present in the incoming directory
     *
     * @return array of string : the names of the files present in the incoming directory
     */
    public function getUploadedFileNames(Project $project)
    {
        $uploaded_file_names = [];
        //iterate and show the files in the upload directory

        // Workaround for files bigger than 2Gb:
        $src_dir  = $this->getSrcDir($project);
        $filelist = shell_exec("/usr/bin/find " . $src_dir . " -maxdepth 1 -type f -printf \"%f\\n\"");
        $files    = explode("\n", $filelist);
        // Remove last (empty) element
        array_pop($files);
        foreach ($files as $file) {
            if (! preg_match('/^\./', $file[0])) {
                $uploaded_file_names[] = $file;
            }
        }
        return $uploaded_file_names;
    }

    /**
     * Returns the filename created by appending a timestamp to the names of the file present in
     * the incoming directory
     *
     * @param string $file_name the file name we want to resolve it
     *
     * @return string the created filename
     */
    public function getResolvedFileName($file_name)
    {
        $resolvedName = $file_name . "_" . $_SERVER['REQUEST_TIME'];
        return $resolvedName;
    }

    /**
     * Force the upload directory creation, and move the file $file_name in the good directory
     *
     * @return bool True if file is moved to it's final location (false otherwise)
     */
    public function moveFileForge(FRSFile $file)
    {
        $release = $file->getRelease();
        $project = $release->getProject();
        $src_dir = $this->getSrcDir($project);

        return $this->moveFileForgeFromSrcDir($project, $release, $file, $src_dir);
    }

    public function moveFileForgeFromSrcDir(Project $project, FRSRelease $release, FRSFile $file, string $src_dir): bool
    {
        $unixName = $project->getUnixName(false);

        $upload_sub_dir     = $this->getUploadSubDirectory($release);
        $fileName           = $file->getFileName();
        $filePath           = $this->getResolvedFileName($file->getFileName());
        $ftp_frs_dir_prefix = (string) ForgeConfig::get('ftp_frs_dir_prefix');
        if (! file_exists($ftp_frs_dir_prefix . '/' . $unixName . '/' . $upload_sub_dir . '/' . $filePath)) {
            $cmdFilePath = $unixName . '/' . $upload_sub_dir . '/' . $filePath;

            $process = new Process(array_merge($this->fileforge, [$fileName, $cmdFilePath, $src_dir, $ftp_frs_dir_prefix]));

            $this->logger->debug('execute fileforge ' . $process->getCommandLine());
            $ret_val = $process->run();
            $this->logger->debug('fileforge done with status ' . $ret_val);
            foreach ($process->getIterator() as $line) {
                $this->logger->debug("\t $line");
            }
            if ($process->isSuccessful()) {
                $file->setFileName($upload_sub_dir . '/' . $file->getFileName());
                $file->setFilePath($upload_sub_dir . '/' . $filePath);
                return true;
            }
        }
        return false;
    }

    public function getSrcDir(Project $project)
    {
        return ForgeConfig::get('ftp_incoming_dir');
    }

    /**
     * Get the sub directory where to upload the files
     *
     * @static
     *
     * @return string the sub-directory (wihtout any /) where to upload the file
     */
    public function getUploadSubDirectory(FRSRelease $release)
    {
        return 'p' . $release->getPackageID() . '_r' . $release->getReleaseID();
    }

    /**
     * Get a Release Factory
     *
     * @return Object{FRSReleaseFactory} a FRSReleaseFactory Object.
     */
    public function _getFRSReleaseFactory()
    {
        if (empty($this->release_factory)) {
            $this->release_factory = new FRSReleaseFactory();
        }
        return $this->release_factory;
    }

    public function _delete($file_id)
    {
        $_id  = (int) $file_id;
        $file = $this->getFRSFileFromDb($_id);
        $dao  = $this->_getFRSFileDao();
        if ($dao->delete($_id)) {
            $this->_getEventManager()->processEvent(
                'frs_delete_file',
                ['group_id' => $file->getGroup()->getGroupId(),
                    'item_id'    => $_id,
                ]
            );
            return true;
        }
        return false;
    }

    /**
     * Mark a file as deleted
     *
     * Deletion of a file in FRS is a complex process.
     * First, when user attempt to delete a file (or recursively when she deletes
     * a release or a whole package) files are not immedialty deleted:
     * #1: Flag the file as deleted (status = D, no longer appears in web interface)
     * #2: Move flaged files into a staging area for a while
     * #3: Every so often permanently erase from the file system the files from
     *     the stagging area that are older than a given threshold.
     * Why such complex process ?
     * #1: To allow files to be backed-up even if files are uploaded and deleted
     *     before a backup job occurs.
     * #2: The staging area/period allows site admin to magically restore files
     *     if they were removed by mistake.
     * #3: Previous step 2 needs to be done by 'root' because files might no be
     *     owned by Codendiadm user.
     * #4: Whe need to move files in a staging area because otherwise people would
     *     not be able to upload a file with the same name in the same release
     *     because the new file will override the deleted one and when the job
     *     comes to purge the file it will remove the new one (valid).
     *
     * @param int $group_id
     * @param int $file_id
     *
     * @return bool
     */
    public function delete_file($group_id, $file_id)
    {
        $file = $this->getFRSFileFromDb($file_id, $group_id);
        if ($file) {
            return $this->_delete($file_id);
        }
        return false;
    }

    /**
     * Centralize treatement of files physical deletion in FRS
     *
     * @param int $time Date from when the files must be erased
     * @param Backend $backend
     *
     * @return bool
     */
    public function moveFiles($time, $backend)
    {
        try {
            $moveToStaging  = $this->moveDeletedFilesToStagingArea($backend);
            $purgeFiles     = $this->purgeFiles($time, $backend);
            $cleanStaging   = $this->cleanStaging($backend);
            $restoreDeleted = $this->restoreDeletedFiles($backend);
            return $moveToStaging && $purgeFiles && $cleanStaging && $restoreDeleted;
        } catch (Exception $e) {
            $backend->log($e->getMessage(), Backend::LOG_ERROR);
            return false;
        }
    }

    /**
     * Move to staging all files marked as deleted but still in the release area
     *
     * @param BackendSystem $backend
     *
     * @return bool
     */
    public function moveDeletedFilesToStagingArea($backend)
    {
        $dao = $this->_getFRSFileDao();
        $dar = $dao->searchStagingCandidates();
        if ($dar && ! $dar->isError()) {
            $moveStatus = true;
            foreach ($dar as $row) {
                $file = new FRSFile($row);
                if (! $this->moveDeletedFileToStagingArea($file, $backend)) {
                    $moveStatus = false;
                }
            }
            return $moveStatus;
        }
        $backend->log("Error while searching staging candidates", Backend::LOG_ERROR);
        return false;
    }

    /**
     * Physically move one file from release area to staging
     *
     * The file is renamed during the move with its file id to avoid override
     * if someone upload and delete 2 times (or more) the same file in the same
     * release
     *
     * @param FRSFile       $file
     * @param BackendSystem $backend
     *
     * @return bool
     */
    public function moveDeletedFileToStagingArea($file, $backend)
    {
        $stagingPath = $this->getStagingPath($file);
        $stagingDir  = dirname($stagingPath);
        $moveStatus  = true;
        $dao         = $this->_getFRSFileDao();
        if (! is_dir($stagingDir)) {
            $moveStatus = mkdir($stagingDir, 0750, true);
        }
        $moveStatus = $dao->setFileInDeletedList($file->getFileId()) && $moveStatus;
        if (file_exists($file->getFileLocation())) {
            $moveStatus = rename($file->getFileLocation(), $stagingPath) && $moveStatus;
        } else {
            $dao->setPurgeDate($file->getFileId(), $_SERVER['REQUEST_TIME']);
            $backend->log("File " . $file->getFileLocation() . "(" . $file->getFileID() . ") doesn't exist. It cannot be moved to staging area. Marked as purged", Backend::LOG_WARNING);
            $moveStatus = false;
        }
        if (! $moveStatus) {
            $backend->log("Error while moving file " . $file->getFileLocation() . "(" . $file->getFileID() . ") to staging area", Backend::LOG_ERROR);
        }
        if (! $this->deleteEmptyReleaseDirectory($file, $backend)) {
            $moveStatus = false;
        }
        return $moveStatus;
    }

    /**
     * Remove Files, releases and packages for a given project.
     *
     * @param int $groupId
     * @param BackendSystem $backend
     *
     * @return bool
     */
    public function deleteProjectFRS($groupId, $backend)
    {
        $deleteState = true;
        $frsrf       = $this->_getFRSReleaseFactory();
        if (! $frsrf->deleteProjectReleases($groupId)) {
            $deleteState = false;
        }
        $frspf = $frsrf->_getFRSPackageFactory();
        if (! $frspf->deleteProjectPackages($groupId)) {
            $deleteState = false;
        }
        if (! $this->moveDeletedFilesToStagingArea($backend)) {
            $deleteState = false;
        }
        return $deleteState;
    }

    /**
     * If release directory is empty, delete it
     *
     * @param FRSFile $file
     *
     * @return bool
     */
    public function deleteEmptyReleaseDirectory($file, $backend)
    {
        $nbFiles   = 0;
        $directory = dirname($file->getFileLocation());
        try {
            $dir = new DirectoryIterator($directory);
            foreach ($dir as $f) {
                if (! $f->isDot()) {
                    $nbFiles++;
                }
            }
            if ($nbFiles === 0) {
                rmdir(dirname($file->getFileLocation()));
            }
            return true;
        } catch (RuntimeException $e) {
            $backend->log("Directory " . $directory . " already deleted", "warn");
            return true;
        } catch (Exception $e) {
            $backend->log("Error while deleting empty release directory " . $directory, Backend::LOG_ERROR);
            return false;
        }
    }

    /**
     * Get the path in staging area of a file
     *
     * @param FRSFile $file
     *
     * @return String
     */
    public function getStagingPath($file)
    {
        $fileName    = basename($file->getFileLocation());
        $releasePath = dirname($file->getFileLocation());
        $relDirName  = basename($releasePath);
        $prjDirName  = basename(dirname($releasePath));
        $stagingPath = ForgeConfig::get('ftp_frs_dir_prefix') . '/DELETED/' . $prjDirName . '/' . $relDirName;
        return $stagingPath . '/' . $fileName . '.' . $file->getFileId();
    }

    /**
     * Permanently erase from the file system all deleted files older than given date
     *
     * @param int $time Timestamp
     * @param Backend $backend
     *
     * @return bool
     */
    public function purgeFiles($time, $backend)
    {
        $dao = $this->_getFRSFileDao();
        $dar = $dao->searchFilesToPurge($time);
        if ($dar && ! $dar->isError()) {
            $purgeState = true;
            if ($dar->rowCount() > 0) {
                foreach ($dar as $row) {
                    $file       = new FRSFile($row);
                    $purgeState = $purgeState & $this->purgeFile($file, $backend);
                }
            }
            return (bool) $purgeState;
        }
        return false;
    }

    /**
     * Invoque 'archive deleted item' hook in order to make a backup of a given file.
     * This method should be used whithin the FRS purge process
     *
     * @param FRSFile $file File to archive
     * @param Backend $backend Backend
     *
     * @return bool
     */
    public function archiveBeforePurge($file, $backend)
    {
        $release = $this->_getFRSReleaseFactory()->getFRSReleaseFromDb($file->getReleaseId(), null, null, true);
        $sub_dir = $this->getUploadSubDirectory($release);
        $prefix  = $file->getGroup()->getGroupId() . '_' . $sub_dir . '_' . $file->getFileID();

        $event = new ArchiveDeletedItemEvent(new ArchiveDeletedItemFileProvider($this->getStagingPath($file), $prefix), true);

        $this->_getEventManager()->processEvent($event);

        return $event->isSuccessful();
    }

    /**
     * Erase from the file system one file
     *
     * @param FRSFile $file    File to delete
     * @param Backend $backend Backend
     *
     * @return bool
     */
    public function purgeFile($file, $backend)
    {
        $dao = $this->_getFRSFileDao();
        if (file_exists($this->getStagingPath($file))) {
            $has_been_archived = $this->archiveBeforePurge($file, $backend);
            if ($has_been_archived) {
                if (unlink($this->getStagingPath($file))) {
                    if (! $dao->setPurgeDate($file->getFileID(), time())) {
                        $backend->log("File " . $this->getStagingPath($file) . " not purged, Set purge date in DB fail", Backend::LOG_ERROR);
                        return false;
                    }
                    return true;
                }
            }
            $backend->log("File " . $this->getStagingPath($file) . " not purged, unlink failed", Backend::LOG_ERROR);
            return false;
        }
        if (! $dao->setPurgeDate($file->getFileID(), time())) {
            $backend->log("File " . $this->getStagingPath($file) . " not found on file system and not purged, Set purge date in DB fail", Backend::LOG_ERROR);
            return false;
        }
        $backend->log("File " . $this->getStagingPath($file) . " not found on file system, automatically marked as purged", Backend::LOG_WARNING);
        return true;
    }

    /**
     * Remove empty releases and project directories in staging area
     *
     * @param Backend $backend
     *
     * @return bool
     */
    public function cleanStaging($backend)
    {
        // All projects
        $prjIter = new DirectoryIterator(ForgeConfig::get('ftp_frs_dir_prefix') . '/DELETED');
        foreach ($prjIter as $prj) {
            if (strpos($prj->getFilename(), '.') !== 0) {
                // Releases
                $nbRel   = 0;
                $relIter = new DirectoryIterator($prj->getPathname());
                foreach ($relIter as $rel) {
                    if (! $rel->isDot()) {
                        // Files
                        $nbFiles  = 0;
                        $fileIter = new DirectoryIterator($rel->getPathname());
                        foreach ($fileIter as $file) {
                            if (! $file->isDot()) {
                                $nbFiles++;
                            }
                        }
                        if ($nbFiles === 0) {
                            if (! rmdir($rel->getPathname())) {
                                $backend->log("Error while removing " . $rel->getPathname() . "release folder", Backend::LOG_ERROR);
                                return false;
                            }
                        } else {
                            $nbRel++;
                        }
                    }
                }
                if ($nbRel === 0) {
                    if (! rmdir($prj->getPathname())) {
                        $backend->log("Error while removing " . $prj->getPathname() . " project folder", Backend::LOG_ERROR);
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * List all files deleted but not already purged
     *
     * @param int $groupId
     * @param int $offset
     * @param int $limit
     *
     * @return LegacyDataAccessResultInterface
     */
    public function listPendingFiles($groupId, $offset, $limit)
    {
        $dao = $this->_getFRSFileDao();
        return $dao->searchFilesToPurge($_SERVER['REQUEST_TIME'], $groupId, $offset, $limit);
    }

    /**
     * List all deleted files marked to be restored
     *
     * @param int $groupId
     *
     * @return LegacyDataAccessResultInterface
     */
    public function listToBeRestoredFiles($groupId)
    {
        $dao = $this->_getFRSFileDao();
        return $dao->searchFilesToRestore($groupId);
    }

    /**
     * List all files deleted but not yet moved to staging area
     *
     * @param int $groupId
     *
     * @return LegacyDataAccessResultInterface
     */
    public function listStagingCandidates($groupId)
    {
        $dao = $this->_getFRSFileDao();
        return $dao->searchStagingCandidates($groupId);
    }

    /**
     * @return FRSPermissionManager
     */
    private function getPermissionManager()
    {
        return FRSPermissionManager::build();
    }

    /**
     * @return bool true if the user has permission to add files, false otherwise
     */
    public function userCanAdd($project_id, $user_id = false)
    {
        $project_manager = $this->_getProjectManager();
        $project         = $project_manager->getProject($project_id);
        $user_manager    = UserManager::instance();

        if (! $user_id) {
            $user = $user_manager->getCurrentUser();
        } else {
            $user = $user_manager->getUserById($user_id);
        }

        return $this->getPermissionManager()->isAdmin($project, $user);
    }

    /**
     * Wrapper to get a UserManager instance
     *
     * @return UserManager
     */
    public function _getUserManager()
    {
        $um = UserManager::instance();
        return $um;
    }

    /**
     * Wrapper to get an EventManager instance
     *
     * @return EventManager
     */
    public function _getEventManager()
    {
        $em = EventManager::instance();
        FRSLog::instance();
        return $em;
    }

    /**
     * Wrapper to get a SystemEventManager instance
     *
     * @return SystemEventManager
     */
    public function _getSystemEventManager()
    {
        return SystemEventManager::instance();
    }

    /**
     * Wrapper to get ProjectManager instance
     *
     * @return ProjectManager
     */
    public function _getProjectManager()
    {
        return ProjectManager::instance();
    }

    /**
     * restore file by moving it from staging area to its old location
     *
     * @param FRSFile $file
     *
     * @return bool
     */
    public function restoreFile($file, $backend)
    {
        $release = $this->_getFRSReleaseFactory()->getFRSReleaseFromDb($file->getReleaseId(), null, null, true);
        $dao     = $this->_getFRSFileDao();
        if (! $release->isDeleted()) {
            $stagingPath = $this->getStagingPath($file);
            if (file_exists($stagingPath)) {
                if (! is_dir(dirname($file->getFileLocation()))) {
                    mkdir(dirname($file->getFileLocation()), 0755, true);
                    $backend->chgrp(dirname($file->getFileLocation()), ForgeConfig::get('sys_http_user'));
                }
                if (rename($stagingPath, $file->getFileLocation())) {
                    if ($dao->restoreFile($file->getFileID())) {
                        $this->_getEventManager()->processEvent(
                            'frs_restore_file',
                            ['group_id' => $file->getGroup()->getGroupId(),
                                'item_id'  => $file->getFileID(),
                            ]
                        );
                        return true;
                    }
                    $backend->log("File " . $file->getFileLocation() . "(" . $file->getFileID() . ") not restored, database error", Backend::LOG_ERROR);
                    return false;
                }
            }
            $backend->log("File " . $file->getFileLocation() . "(" . $file->getFileID() . ") could not be restored, not found in staging path " . $stagingPath, Backend::LOG_ERROR);
            return false;
        }
        $dao->cancelRestore($file->getFileID());
        $backend->log("File " . $file->getFileLocation() . "(" . $file->getFileID() . ") could not be restored in deleted release " . $release->getName() . "(" . $release->getReleaseID() . ")", Backend::LOG_ERROR);
        return false;
    }

    /**
     * Restore files marked to be restored
     *
     * @return bool
     */
    public function restoreDeletedFiles($backend)
    {
        $dao = $this->_getFRSFileDao();
        $dar = $dao->searchFilesToRestore();
        if ($dar && ! $dar->isError()) {
            if ($dar->rowCount() > 0) {
                $restoreState = true;
                foreach ($dar as $row) {
                    $file = new FRSFile($row);
                    if (! $this->restoreFile($file, $backend)) {
                        $restoreState = false;
                    }
                }
                return $restoreState;
            }
            return true;
        }
        return false;
    }

    /**
     * Mark the files that site admin wants to restore
     *
     * @param FRSFile $file
     *
     * @return bool
     */
    public function markFileToBeRestored($file)
    {
        $release = $this->_getFRSReleaseFactory()->getFRSReleaseFromDb($file->getReleaseID(), null, null, true);
        if (! $release->isDeleted()) {
            $dao = $this->_getFRSFileDao();
            return $dao->markFileToBeRestored($file->getFileID());
        }
        return false;
    }

    /**
     * Insert the computed md5sum value in case of offline checksum compute
     *
     * @param int $fileId
     * @param String $md5Computed
     *
     * @return bool
     */

    public function updateComputedMd5sum($fileId, $md5Computed)
    {
        $dao = $this->_getFRSFileDao();
        return $dao->updateComputedMd5sum($fileId, $md5Computed);
    }

    /**
     * Compare md5sums to check if they fit
     * Note : Empty fields make coparison pass
     *
     * @param String $computedMd5
     * @param String $referenceMd5
     *
     * @return bool
     */
    public function compareMd5Checksums($computedMd5, $referenceMd5)
    {
        return($computedMd5 == '' || $referenceMd5 == '' || strcasecmp($computedMd5, $referenceMd5) == 0);
    }
}
