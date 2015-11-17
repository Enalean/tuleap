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

class SVNXMLImporter {
    /** @var Logger */
    private $logger;

    /** @var SvnNotificationDao */
    private $notification_dao;

    public function __construct(Logger $logger, SvnNotificationDao $notification_dao = null) {
        $this->notification_dao = $notification_dao;
        $this->logger = $logger;
    }

    // @return boolean specifying the success of the import
    public function import(Project $project, SimpleXMLElement $xml_input, $extraction_path) {
        $xml_svn = $xml_input->svn;
        if(!$xml_svn) {
            return true;
        }

        $dumpfile_ok = $this->import_dumpfile($project, $xml_svn, $extraction_path);
        $notification_ok = $this->import_notification($project, $xml_svn);

        return $dumpfile_ok && $notification_ok;
    }

    // @return boolean true on success, false on failure
    private function import_dumpfile(Project $project, $xml_svn, $extraction_path) {
        $attrs = $xml_svn->attributes();
        if(!isset($attrs['dump-file'])) return true;

        $rootpath_arg = escapeshellarg($project->getSVNRootPath());
        $dumpfile_arg = escapeshellarg("$extraction_path/{$attrs["dump-file"]}");
        $commandline  = "svnadmin load $rootpath_arg <$dumpfile_arg 2>&1";

        $this->logger->info($commandline);

        try {
            $cmd = new System_Command();
            $command_output = $cmd->exec($commandline);
            $return_status = 0;
        } catch (System_Command_CommandException $e) {
            $command_output = $e->output;
            $return_status = $e->return_value;
        }

        foreach($command_output as $line) {
            $this->logger->debug($line);
        }
        $this->logger->debug("Exited with status $return_status");

        return 0 === $return_status;
    }

    // @return boolean true on success, false on failure
    private function import_notification(Project $project, $xml_svn) {
        $dao = $this->getNotificationDAO();
        $res = true;
        foreach($xml_svn->notification as $notif) {
            $attrs = $notif->attributes();
            $path = $attrs['path'];
            $emails = $attrs['emails'];
            $ok = $dao->setSvnMailingList($project->getID(), $emails, $path);
            if (!$ok) $res = false;
        }
        return $res;
    }

    // @return SvnNotificationDao the Notification DAO
    private function getNotificationDAO() {
        if(empty($this->notification_dao)) {
            $this->notification_dao = new SvnNotificationDao(CodendiDataAccess::instance());
        }
        return $this->notification_dao;
    }
}

?>
