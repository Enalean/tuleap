<?php
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
//
//
//
//
//    Originally by to the SourceForge Team,1999-2000
//
//  Written for Codendi by Thierry Jacquin
//require_once('common/tracker/ArtifactCanned.class.php');


class ArtifactCannedHtml extends ArtifactCanned
{

    /**
     *  ArtifactCannedHtml() - constructor
     *
     *  @param $artifact_type - the ArtifactType object embedding this ArtifactCanned sets
     */
    public function __construct(&$artifact_type)
    {
        return parent::__construct($artifact_type);
    }

    /**
     *  Display the create canned form
     *
     *  @return void
     */
    public function displayCreateForm()
    {
        global $Language;

        echo '<h3>' . $Language->getText('tracker_include_canned', 'create_response') . '</h3>';
        $atid = $this->ArtifactType->getID();
        $g = $this->ArtifactType->getGroup();
        $group_id = $g->getID();
        echo '<P>';
        echo $Language->getText('tracker_include_canned', 'save_time');
        echo '<P>';
        echo '<FORM ACTION="/tracker/admin/" METHOD="POST">';
        echo '<INPUT TYPE="HIDDEN" NAME="func" VALUE="canned">';
        echo '<INPUT TYPE="HIDDEN" NAME="create_canned" VALUE="1">';
        echo '<INPUT TYPE="HIDDEN" NAME="atid" VALUE="' . (int) $atid . '">';
        echo '<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="' . (int) $group_id . '">';
        echo '<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="1">';
        echo '<B>' . $Language->getText('tracker_include_canned', 'title') . ':</B><BR>';
        echo '<INPUT TYPE="TEXT" NAME="title" VALUE="" SIZE="50" MAXLENGTH="50">';
        echo '<P>';
        echo '<B>' . $Language->getText('tracker_include_canned', 'message_body') . '</B><BR>';
        echo '<TEXTAREA NAME="body" ROWS="20" COLS="65" WRAP="HARD"></TEXTAREA>';
        echo '<P>';
        echo '<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">';
        echo '</FORM>';
    }

/**
     *  Display the update canned form
     *
     *  @return void
     */
    public function displayUpdateForm()
    {
        global $Language;
        echo "<P>";
        $atid = $this->ArtifactType->getID();
        $id =
        $g = $this->ArtifactType->getGroup();
        $group_id = $g->getID();
        $id = $this->getID();
         echo $Language->getText('tracker_include_canned', 'save_time');
         echo '<P>';
         echo '<FORM ACTION="/tracker/admin/" METHOD="POST">';
         echo '<INPUT TYPE="HIDDEN" NAME="func" VALUE="canned">';
         echo '<INPUT TYPE="HIDDEN" NAME="update_canned" VALUE="1">';
         echo '<INPUT TYPE="HIDDEN" NAME="atid" VALUE="' . (int) $atid . '">';
         echo '<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="' . (int) $group_id . '">';
         echo '<INPUT TYPE="HIDDEN" NAME="artifact_canned_id" VALUE="' . (int) $id . '">';
         echo '<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="1">';
         echo '<B>' . $Language->getText('tracker_include_canned', 'title') . ':</B><BR>';
         echo '<INPUT TYPE="TEXT" NAME="title" VALUE="' . $this->getTitle() . '" SIZE="50" MAXLENGTH="50">';
         echo '<P>';
         echo '<B>' . $Language->getText('tracker_include_canned', 'message_body') . '</B><BR>';
         echo '<TEXTAREA NAME="body" ROWS="20" COLS="65" WRAP="HARD">' . $this->getBody() . '</TEXTAREA>';
         echo '<P>';
         echo '<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">';
         echo '</FORM>';
    }



    /**
     *  Display the different Canned Responses associated to this tracker
     *
     *  @return void
     */
    public function displayCannedResponses()
    {
        global $Language;
        $group_id = $this->ArtifactType->Group->getID();
        $atid = $this->ArtifactType->getID();
        $hp = Codendi_HTMLPurifier::instance();
        echo '<H2>' . $Language->getText('tracker_import_admin', 'tracker') .
         ' \'<a href="/tracker?group_id=' . (int) $group_id . '&atid=' . (int) $atid . '">' . $hp->purify(SimpleSanitizer::unsanitize($this->ArtifactType->getName()), CODENDI_PURIFIER_CONVERT_HTML) . '</a>\' - ' .
        $Language->getText('tracker_admin_index', 'create_modify_cannedresponse') . '</a></H2>';
        $result = $this->ArtifactType->getCannedResponses();
        $rows = db_numrows($result);
        echo "<P>";

        if ($result && $rows > 0) {
         /*
           Links to update pages
           */
            echo "\n<H3>" . $Language->getText('tracker_include_canned', 'existing_responses') . "</H3><P>";

            $title_arr = array();
            $title_arr[] = $Language->getText('tracker_include_canned', 'title');
            $title_arr[] = $Language->getText('tracker_include_canned', 'body_extract');
            $title_arr[] = $Language->getText('tracker_include_canned', 'delete');

            echo html_build_list_table_top($title_arr);
            $atid = $this->ArtifactType->getID();
            $g = $this->ArtifactType->getGroup();
            $group_id = $g->getID();
            for ($i = 0; $i < $rows; $i++) {
                  echo '<TR class="' . util_get_alt_row_color($i) . '">' .
                  '<TD><A HREF="/tracker/admin?func=canned&update_canned=1&artifact_canned_id=' .
                  (int) (db_result($result, $i, 'artifact_canned_id')) . '&atid=' . (int) $atid . '&group_id=' . (int) $group_id . '">' .
                $hp->purify(util_unconvert_htmlspecialchars(db_result($result, $i, 'title')), CODENDI_PURIFIER_CONVERT_HTML) . '</A></TD>' .
                  '<TD>' . $hp->purify(util_unconvert_htmlspecialchars(substr(db_result($result, $i, 'body'), 0, 160)), CODENDI_PURIFIER_CONVERT_HTML) .
                  '<b>...</b></TD>' .
                  '<td align="center"><A HREF="/tracker/admin/?func=canned&delete_canned=1&artifact_canned_id=' .
                  (int) (db_result($result, $i, 'artifact_canned_id')) . '&atid=' . (int) $atid . '&group_id=' . (int) $group_id .
                '" onClick="return confirm(\'' . addslashes($Language->getText('tracker_include_canned', 'delete_canned', db_result($result, $i, 'title'))) . '\')">' .
                '<img src="' . util_get_image_theme("ic/trash.png") . '" border="0"></A></td></TR>';
            }
            echo '</TABLE>';
        } else {
            echo "\n<H3>" . $Language->getText('tracker_include_canned', 'no_canned_response') . "</H3>";
        }
    }
}
