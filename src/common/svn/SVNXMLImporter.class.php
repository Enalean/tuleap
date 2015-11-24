<?php
/**
 * Copyright (c) Sogilis, 2015. All Rights Reserved.
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

class SVNXMLImporterException extends Exception {
}

class SVNXMLImporter {
    /** @var Logger */
    private $logger;

    /** @var SvnNotificationDao */
    private $notification_dao;

    /** @var SVN_AccessFile_DAO */
    private $accessfile_dao;

    /** @var XML_RNGValidator */
    private $xml_validator;

    public function __construct(
        Logger $logger,
        XML_RNGValidator $xml_validator = null,
        SvnNotificationDao $notification_dao = null,
        SVN_AccessFile_DAO $accessfile_dao = null)
    {
        $this->notification_dao = $notification_dao;
        $this->accessfile_dao = $accessfile_dao;
        $this->logger = $logger;
        $this->xml_validator = $xml_validator;

        if (empty($this->xml_validator)) {
            $this->xml_validator = new XML_RNGValidator();
        }
    }

    // @return boolean specifying the success of the import
    public function import(Project $project, SimpleXMLElement $xml_input, $extraction_path) {
        $xml_svn = $xml_input->svn;
        if(!$xml_svn) {
            return true;
        }

        $rng_path = realpath(dirname(__FILE__).'/../xml/resources/svn.rng');
        $this->xml_validator->validate($xml_svn, $rng_path);
        $this->logger->debug("XML tag <svn/> is valid");

        $this->importDumpFile($project, $xml_svn, $extraction_path);
        $this->importNotification($project, $xml_svn);
        $this->importAccessFile($project, $xml_svn);
    }

    // @return boolean true on success, false on failure
    private function importDumpFile(Project $project, $xml_svn, $extraction_path) {
        $attrs = $xml_svn->attributes();
        if(!isset($attrs['dump-file'])) return true;

        $rootpath_arg = escapeshellarg($project->getSVNRootPath());
        $dumpfile_arg = escapeshellarg("$extraction_path/{$attrs["dump-file"]}");
        $commandline  = "svnadmin load $rootpath_arg <$dumpfile_arg 2>&1";

        $this->logger->info($commandline);

        try {
            $cmd = new System_Command();
            $command_output = $cmd->exec($commandline);
            foreach($command_output as $line) {
                $this->logger->debug("svnadmin: $line");
            }
            $this->logger->debug("svnadmin returned with status 0");
        } catch (System_Command_CommandException $e) {
            foreach($e->output as $line) {
                $this->logger->error("svnadmin: $line");
            }
            $this->logger->error("svnadmin returned with status {$e->return_value}");
            throw new SVNXMLImporterException(
                "failed to svnadmin load $dumpfile_arg in $rootpath_arg:".
                " exited with status {$e->return_value}");
        }
    }

    // @return boolean true on success, false on failure
    private function importNotification(Project $project, $xml_svn) {
        $dao = $this->getNotificationDAO();
        foreach($xml_svn->notification as $notif) {
            $attrs = $notif->attributes();
            $path = $attrs['path'];
            $emails = $attrs['emails'];
            $ok = $dao->setSvnMailingList($project->getID(), $emails, $path);
            if (!$ok) {
                throw new SVNXMLImporterException("Could not set svn mailing lists");
            }
        }
    }

    // @return boolean true on success, false on failure
    private function importAccessFile(Project $project, $xml_svn) {
        $dao = $this->getAccessFileDAO();
        $tagname = "access-file";
        $contents = (string) $xml_svn->$tagname . "\n";
        $writer = new SVN_AccessFile_Writer($project->getSVNRootPath());

        $this->logger->debug("Write SVN AccessFile: " . $writer->filename());

        if(!$dao->saveNewAccessFileVersionInProject($project->getID(), $contents)) {
            throw new SVNXMLImporterException("Could not save new access file version");
        }
        if(!$writer->write_with_defaults($contents)) {
            throw new SVNXMLImporterException("Could not write to " . $writer->filename());
        }
    }

    // @return SvnNotificationDao the Notification DAO
    private function getNotificationDAO() {
        if(empty($this->notification_dao)) {
            $this->notification_dao = new SvnNotificationDao(CodendiDataAccess::instance());
        }
        return $this->notification_dao;
    }

    // @return SVN_AccessFile_DAO the AccessFile DAO
    private function getAccessFileDAO() {
        if(empty($this->accessfile_dao)){
            $this->accessfile_dao = new SVN_AccessFile_DAO();
        }
        return $this->accessfile_dao;
    }
}

?>
