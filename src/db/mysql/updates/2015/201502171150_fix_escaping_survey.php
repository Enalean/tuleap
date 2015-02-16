<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class b201502171150_fix_escaping_survey extends ForgeUpgrade_Bucket {
    public function description() {
        return "Fix escaping in survey service";
    }

    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up() {
        $this->updateSurveys();
        $this->updateSurveyQuestions();
        $this->updateSurveyResponses();
    }

    private function updateSurveys() {
        $sql = "UPDATE surveys SET survey_title = "
                . "REPLACE("
                    . "REPLACE("
                        . "REPLACE("
                            . "REPLACE("
                                . "REPLACE(survey_title, '&nbsp;', ' '), "
                            . "'&quot;', '\"'), "
                        . "'&gt;', '>'), "
                    . "'&lt;', '<'), "
                . "'&amp;', '&'), survey_questions = "
                . "REPLACE("
                    . "REPLACE("
                        . "REPLACE("
                            . "REPLACE("
                                . "REPLACE(survey_questions, '&nbsp;', ' '), "
                            . "'&quot;', '\"'), "
                        . "'&gt;', '>'), "
                    . "'&lt;', '<'), "
                . "'&amp;', '&')";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while transforming data in surveys table.');
        }
    }

    private function updateSurveyQuestions() {
        $sql = "UPDATE survey_questions SET question = "
                . "REPLACE("
                    . "REPLACE("
                        . "REPLACE("
                            . "REPLACE("
                                . "REPLACE(question, '&nbsp;', ' '), "
                            . "'&quot;', '\"'), "
                        . "'&gt;', '>'), "
                    . "'&lt;', '<'), "
                . "'&amp;', '&')";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while transforming data in survey questions table.');
        }
    }

    private function updateSurveyResponses() {
        $sql = "UPDATE survey_responses SET response = "
                . "REPLACE("
                    . "REPLACE("
                        . "REPLACE("
                            . "REPLACE("
                                . "REPLACE(response, '&nbsp;', ' '), "
                            . "'&quot;', '\"'), "
                        . "'&gt;', '>'), "
                    . "'&lt;', '<'), "
                . "'&amp;', '&')";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while transforming data in survey responses table.');
        }
    }
}
