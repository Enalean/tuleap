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

    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }

    // @return SVNXMLImporter
    public static function build(Logger $logger) {
        return new SVNXMLImporter($logger);
    }

    // @return boolean specifying the success of the import
    public function import(Project $project, SimpleXMLElement $xml_input, $extraction_path) {
        $xml_svn = $xml_input->svn;
        if(!$xml_svn) {
            return true;
        }

        $attrs = $xml_svn->attributes();

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
}

?>
