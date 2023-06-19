<?php
/**
 * Copyright (c) Enalean 2017-Present. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Option\Option;
use Tuleap\REST\BasicAuthentication;
use Tuleap\REST\RESTCurrentUserMiddleware;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\File\AttachmentForRestCreator;
use Tuleap\Tracker\FormElement\Field\File\AttachmentForTraditionalUploadCreator;
use Tuleap\Tracker\FormElement\Field\File\AttachmentForTusUploadCreator;
use Tuleap\Tracker\FormElement\Field\File\AttachmentToFinalPlaceMover;
use Tuleap\Tracker\FormElement\Field\File\ChangesetValueFileSaver;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\File\FieldDataFromRESTBuilder;
use Tuleap\Tracker\FormElement\Field\File\FileFieldValueDao;
use Tuleap\Tracker\FormElement\Field\File\FileInfoForTusUploadedFileReadyToBeAttachedProvider;
use Tuleap\Tracker\FormElement\Field\File\Upload\FileOngoingUploadDao;
use Tuleap\Tracker\FormElement\Field\File\Upload\Tus\FileBeingUploadedInformationProvider;
use Tuleap\Tracker\FormElement\Field\File\Upload\UploadPathAllocator;
use Tuleap\Tracker\Report\Query\ParametrizedFrom;
use Tuleap\Tracker\Report\Query\ParametrizedSQLFragment;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_FormElement_Field_File extends Tracker_FormElement_Field
{
    public function getCriteriaFrom(Tracker_Report_Criteria $criteria): Option
    {
        //Only filter query if field  is used
        if ($this->isUsed()) {
            $criteria_value = $this->getCriteriaValue($criteria);
            //Only filter query if criteria is valuated
            if ($criteria_value) {
                $a = 'A_' . $this->id;
                $b = 'B_' . $this->id;
                $c = 'C_' . $this->id;

                $da             = \Tuleap\DB\DBFactory::getMainTuleapDBConnection()->getDB();
                $criteria_value = '%' . $da->escapeLikeValue($criteria_value) . '%';

                return Option::fromValue(new ParametrizedFrom(
                    " INNER JOIN tracker_changeset_value AS $a ON ($a.changeset_id = c.id AND $a.field_id = ? )
                         INNER JOIN tracker_changeset_value_file AS $b ON ($b.changeset_value_id = $a.id)
                         INNER JOIN tracker_fileinfo AS $c ON (
                            $c.id = $b.fileinfo_id
                            AND (
                                $c.description LIKE ?
                                OR
                                $c.filename LIKE ?
                            )
                         ) ",
                    [
                        $this->id,
                        $criteria_value,
                        $criteria_value,
                    ]
                ));
            }
        }

        return Option::nothing(ParametrizedFrom::class);
    }

    public function getCriteriaWhere(Tracker_Report_Criteria $criteria): Option
    {
        return Option::nothing(ParametrizedSQLFragment::class);
    }

    public function getQuerySelect(): string
    {
        return '';
    }

    public function getQueryFrom()
    {
        return '';
    }

    protected function getCriteriaDao()
    {
        return new Tracker_Report_Criteria_File_ValueDao();
    }

    public function fetchChangesetValue(
        int $artifact_id,
        int $changeset_id,
        mixed $value,
        ?Tracker_Report $report = null,
        ?int $from_aid = null,
    ): string {
        $html             = '';
        $submitter_needed = true;
        $html            .= $this->fetchAllAttachment($artifact_id, $this->getChangesetValues($changeset_id), $submitter_needed, []);
        return $html;
    }

    /**
     * Display the field as a Changeset value.
     * Used in CSV data export.
     *
     * @param int $artifact_id the corresponding artifact id
     * @param int $changeset_id the corresponding changeset
     * @param mixed $value the value of the field
     *
     * @return string
     */
    public function fetchCSVChangesetValue($artifact_id, $changeset_id, $value, $report)
    {
        return $this->fetchAllAttachmentForCSV($artifact_id, $this->getChangesetValues($changeset_id));
    }

    public function fetchCriteriaValue($criteria)
    {
        $html = '<input type="text" name="criteria[' . $this->id . ']" id="tracker_report_criteria_' . $this->id . '" value="';
        if ($criteria_value = $this->getCriteriaValue($criteria)) {
            $hp    = Codendi_HTMLPurifier::instance();
            $html .= $hp->purify($criteria_value, CODENDI_PURIFIER_CONVERT_HTML);
        }
        $html .= '" />';
        return $html;
    }

    /**
     * Fetch the value
     * @param mixed $value the value of the field
     * @return string
     */
    public function fetchRawValue($value)
    {
        return $value;
    }

    /**
     * Fetch the value in a specific changeset
     * @param Tracker_Artifact_Changeset $changeset
     * @return string
     */
    public function fetchRawValueFromChangeset($changeset)
    {
        $value = '';
        if ($v = $changeset->getValue($this)) {
            assert($v instanceof Tracker_Artifact_ChangesetValue_File);
            if (isset($v['value_id'])) {
                $v = [$v];
            }
            /** @psalm-var array{value_id:int} $val */
            foreach ($v as $val) {
                if ($val['value_id'] != 100) {
                    if ($row = $this->getValueDao()->searchById($val['value_id'], $this->id)->getRow()) {
                        if ($value) {
                            $value .= ', ';
                        }
                        $value .= $row['filename'];
                    }
                }
            }
        }
        return $value;
    }

    protected function getValueDao()
    {
        return new FileFieldValueDao();
    }

    /**
     * Fetch the html code to display the field value in artifact
     *
     * @param Artifact                        $artifact         The artifact
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param array                           $submitted_values The value already submitted by the user
     *
     * @return string
     */
    protected function fetchArtifactValue(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ) {
        $html             = '';
        $submitter_needed = true;
        $read_only        = false;
        $html            .= $this->fetchAllAttachment($artifact->id, $value, $submitter_needed, $submitted_values, $read_only);
        $html            .= $this->fetchSubmitValue($submitted_values);
        return $html;
    }

    public function fetchArtifactForOverlay(Artifact $artifact, array $submitted_values)
    {
        return $this->fetchArtifactReadOnly($artifact, $submitted_values);
    }

    public function fetchSubmitForOverlay(array $submitted_values)
    {
        return '';
    }

    public function fetchArtifactCopyMode(Artifact $artifact, array $submitted_values)
    {
        $last_changeset = $artifact->getLastChangeset();
        if ($last_changeset) {
            $value = $last_changeset->getValue($this);
            return $this->fetchAllAttachmentTitleAndDescription($value);
        }
        return '';
    }

    /**
     * Fetch the html code to display the field value in Mail
     *
     * @param Artifact                        $artifact The artifact
     * @param PFUser                          $user     The user who will receive the email
     * @param bool                            $ignore_perms
     * @param Tracker_Artifact_ChangesetValue $value    The actual value of the field
     *
     * @return string
     */
    public function fetchMailArtifactValue(
        Artifact $artifact,
        PFUser $user,
        $ignore_perms,
        ?Tracker_Artifact_ChangesetValue $value = null,
        $format = 'text',
    ) {
        if (empty($value) || ! $value->getFiles()) {
            return '-';
        }

        return $this->fetchMailAllAttachment($artifact->id, $value, $format);
    }

    /**
     * Fetch the html code to display the field value in artifact in read only mode
     *
     * @param Artifact                        $artifact The artifact
     * @param Tracker_Artifact_ChangesetValue $value    The actual value of the field
     *
     * @return string
     */
    public function fetchArtifactValueReadOnly(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        $html             = '';
        $submitter_needed = true;
        $html            .= $this->fetchAllAttachment($artifact->id, $value, $submitter_needed, []);
        return $html;
    }

    public function fetchArtifactValueWithEditionFormIfEditable(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ) {
        return $this->fetchArtifactValueReadOnly($artifact, $value) . $this->getHiddenArtifactValueForEdition($artifact, $value, $submitted_values);
    }

    /**
     * Fetch the html code to display the field value in new artifact submission form
     *
     * @return string html
     */
    protected function fetchSubmitValue(array $submitted_values)
    {
        $html  = '';
        $html .= '<div class="add-attachement">';
        $html .= '<p>' . dgettext('tuleap-tracker', 'Add a new file:') . '</p>';
        $html .= '<div class="tracker_artifact_add_attachment">';
        $html .= '<p>';
        $html .= '<input type="file" id="tracker_field_' . $this->id . '" name="artifact[' . $this->id . '][][file]" data-upload-is-enabled/>';
        $html .= '<label>' . dgettext('tuleap-tracker', 'Description:');
        $html .= '</label>';
        $html .= ' <input type="text" id="tracker_field_' . $this->id . '" name="artifact[' . $this->id . '][][description]" />';
        $html .= '</p>';
        $html .= '</div>';
        $html .= '</div>';
        if (isset($submitted_values[$this->id])) {
            foreach ($submitted_values[$this->id] as $submitted_value) {
                if (isset($submitted_value['tus-uploaded-id'])) {
                    $html .= '<input
                        type="hidden"
                        name="artifact[' . $this->id . '][][tus-uploaded-id]"
                        value="' . (int) $submitted_value['tus-uploaded-id'] . '">';
                }
            }
        }
        return $html;
    }

    /**
     * Fetch the html code to display the field value in new artifact submission form
     *
     * @return string html
     */
    protected function fetchSubmitValueMasschange()
    {
        return '';  // deactivate mass change for file fields (see issue described in rev #15855)
    }

    public function fetchAllAttachment(
        $artifact_id,
        $values,
        $submitter_needed,
        array $submitted_values,
        $read_only = true,
        $lytebox_id = null,
    ) {
        $html = '';
        if ($lytebox_id === null) {
            $lytebox_id = $this->getId();
        }
        if ($values !== null && count($values) > 0) {
            $hp    = Codendi_HTMLPurifier::instance();
            $uh    = UserHelper::instance();
            $added = [];
            foreach ($values as $fileinfo) {
                $query_link            = $hp->purify($this->getFileHTMLUrl($fileinfo));
                $sanitized_description = $hp->purify($fileinfo->getDescription(), CODENDI_PURIFIER_CONVERT_HTML);

                $link_show = '<a href="' . $query_link . '"' .
                                 $this->getVisioningAttributeForLink($fileinfo, $read_only, $lytebox_id) . '
                                 title="' . $sanitized_description . '">';

                $add = '<div class="tracker_artifact_attachment">';
                if (! $read_only) {
                    $add .= $this->fetchDeleteCheckbox($fileinfo, $submitted_values);
                }

                $add .= '<div class="tracker_artifact_preview_attachment_hover">';
                if ($submitter_needed) {
                    $add .= '<div class="tracker_artifact_attachment_submitter">' . 'By ' . $uh->getLinkOnUserFromUserId($fileinfo->getSubmittedBy()) . '</div>';
                }
                $add .= '<div class="tracker_artifact_attachment_size">(' . $hp->purify($fileinfo->getHumanReadableFilesize()) . ')</div>';
                $add .= '<div>';
                $add .= $link_show . '<i class="fa fa-eye"></i></a>';
                $add .= '<a href="' . $query_link . '" download><i class="fa fa-download"></i></a>';
                $add .= '</div>';
                $add .= '</div>';

                if ($fileinfo->isImage()) {
                    $query_add = $hp->purify($this->getFileHTMLPreviewUrl($fileinfo));

                    $add .= '<div class="tracker_artifact_preview_attachment image">';
                    $add .= '<div style="background-image: url(\'' . $query_add . '\')"></div>';
                    $add .= '</div>';
                } else {
                    $add .= '<div class="tracker_artifact_preview_attachment"></div>';
                }

                $link_goto = '<a href="' . $query_link . '"' .
                                 'title="' . $sanitized_description . '">';
                $add      .= '<div class="tracker_artifact_attachment_name">' . $link_goto . $hp->purify($fileinfo->getFilename(), CODENDI_PURIFIER_CONVERT_HTML) . '</a></div>';

                if ($sanitized_description) {
                    $add .= '<div class="tracker_artifact_attachment_description">' . $sanitized_description . '</div>';
                }

                $add    .= '</div>';
                $added[] = $add;
            }
            $html .= implode('', $added);
        }

        if ($read_only && ($values === null || count($values) === 0)) {
            $html .= $this->getNoValueLabel();
        }

        return $html;
    }

    public function getFileHTMLUrl(Tracker_FileInfo $file_info)
    {
        $artifact = $this->getFileInfoFactory()->getArtifactByFileInfoId($file_info->getId());
        if (! $artifact) {
            return;
        }

        return TRACKER_BASE_URL . '/attachments/' . $this->getFilenameSlug($file_info);
    }

    public function getFileHTMLPreviewUrl(Tracker_FileInfo $file_info)
    {
        if (! $file_info->isImage()) {
            return;
        }

        $artifact = $this->getFileInfoFactory()->getArtifactByFileInfoId($file_info->getId());

        if (! $artifact) {
            return;
        }

        return TRACKER_BASE_URL . '/attachments/preview/' . $this->getFilenameSlug($file_info);
    }

    /**
     * @return string
     */
    private function getFilenameSlug(Tracker_FileInfo $file_info)
    {
        return (int) $file_info->getId() . '-' . rawurlencode($file_info->getFilename());
    }

    private function getVisioningAttributeForLink($fileinfo, $read_only, $lytebox_id)
    {
        if (! $fileinfo->isImage()) {
            return '';
        }

        if ($read_only) {
            return 'rel="lytebox[' . $lytebox_id . ']"';
        }

        return 'data-rel="lytebox[' . $lytebox_id . ']"';
    }

    private function fetchDeleteCheckbox(Tracker_FileInfo $fileinfo, array $submitted_values)
    {
        $html    = '';
        $html   .= '<label class="pc_checkbox tracker_artifact_attachment_delete">';
        $checked = '';
        if (isset($submitted_values[$this->id]) && ! empty($submitted_values[$this->id]['delete']) && in_array($fileinfo->getId(), $submitted_values[$this->id]['delete'])) {
            $checked = 'checked="checked"';
        }
        $html .= '<input type="checkbox" name="artifact[' . $this->id . '][delete][]" value="' . $fileinfo->getId() . '" title="delete" ' . $checked . ' />&nbsp;';
        $html .= '</label>';
        return $html;
    }

    protected function fetchAllAttachmentForCSV($artifact_id, $values)
    {
        $txt = '';
        if (count($values)) {
            $filenames = [];
            foreach ($values as $fileinfo) {
                $filenames[] = $fileinfo->getFilename();
            }
            $txt .= implode(',', $filenames);
        }
        return $txt;
    }

    protected function fetchAllAttachmentTitleAndDescription($values)
    {
        $html = '';
        if ($values) {
            $purifier = Codendi_HTMLPurifier::instance();
            $html    .= '<div class="tracker-artifact-attachement-title-list tracker_artifact_field"
                              data-field-id="' . $this->id . '"
                              data-is-required="false">';
            $html    .= '<div class="disabled_field">' . dgettext('tuleap-tracker', '"Attachment" type field cannot be modified during artifact copy.') . '</div>';
            $html    .= '<ul>';
            foreach ($values as $value) {
                $description = $value->getDescription();

                $html .= '<li>';
                $html .= '<span class="file-title">';
                $html .= $purifier->purify($value->getFileName());
                $html .= '</span>';

                if ($description) {
                    $html .= '<span class="file-description">';
                    $html .= ' - ' . $purifier->purify($description);
                    $html .= '</span>';
                }
                $html .= '</li>';
            }
            $html .= '</ul>';
            $html .= '</div>';
        }
        return $html;
    }

    /**
     * Fetch all attachements for Mail output
     *
     * @param int $artifact_id The artifact Id
     * @param Array            $values     The actual value of the field
     * @param String            $format       The mail format
     *
     * @return String
     */
    protected function fetchMailAllAttachment($artifact_id, $values, $format)
    {
        $output = '';
        if (! count($values)) {
            return '';
        }

        $uh = UserHelper::instance();

        $url = \Tuleap\ServerHostname::HTTPSUrl();

        if ($format == 'text') {
            foreach ($values as $fileinfo) {
                $query_link = $this->getFileHTMLUrl($fileinfo);

                $link    = '<' . $url . $query_link . '>';
                $output .= $fileinfo->getDescription();
                $output .= ' | ';
                $output .= $fileinfo->getFilename();
                $output .= ' | ';
                $output .= $fileinfo->getHumanReadableFilesize();
                $output .= ' | ';
                $output .= $uh->getDisplayNameFromUserId($fileinfo->getSubmittedBy());
                $output .= PHP_EOL;
                $output .= $link;
                $output .= PHP_EOL;
            }
        } else {
            $hp    = Codendi_HTMLPurifier::instance();
            $added = [];
            foreach ($values as $fileinfo) {
                $query_link            = $hp->purify($this->getFileHTMLUrl($fileinfo));
                $sanitized_description = $hp->purify($fileinfo->getDescription(), CODENDI_PURIFIER_CONVERT_HTML);
                $link_show             = '<a href="' . $url . $query_link . '"
                                 title="' . $sanitized_description . '">';

                $info  = $link_show . $hp->purify($fileinfo->getFilename(), CODENDI_PURIFIER_CONVERT_HTML) . '</a>';
                $info .= ' (' . $hp->purify($fileinfo->getHumanReadableFilesize()) . ')';

                $add     = '<div class="tracker_artifact_attachment">';
                $add    .= '<table><tr><td>';
                $add    .= $info;
                $add    .= '</td></tr></table>';
                $add    .= '</div>';
                $added[] = $add;
            }
            $output .= implode('', $added);
        }
        return $output;
    }

    /**
     * @param int | string $changeset_id
     * @return Tracker_FileInfo[]
     */
    protected function getChangesetValues($changeset_id): array
    {
        $da              = CodendiDataAccess::instance();
        $changest_values = [];

        $field_id     = $da->escapeInt($this->id);
        $changeset_id = $da->escapeInt($changeset_id);
        $sql          = "SELECT c.changeset_id, c.has_changed, f.id
                    FROM tracker_fileinfo as f
                         INNER JOIN tracker_changeset_value_file AS vf on (f.id = vf.fileinfo_id)
                         INNER JOIN tracker_changeset_value AS c
                         ON ( vf.changeset_value_id = c.id
                          AND c.field_id = $field_id AND c.changeset_id= $changeset_id
                         )
                    ORDER BY f.id";

        $dao               = new DataAccessObject();
        $file_info_factory = $this->getTrackerFileInfoFactory();
        foreach ($dao->retrieve($sql) as $row) {
            $changest_values[] = $file_info_factory->getById($row['id']);
        }
        return $changest_values;
    }

    public function previewAttachment($attachment_id)
    {
        if ($fileinfo = $this->getTrackerFileInfoFactory()->getById($attachment_id)) {
            $thumbnail_path = $fileinfo->getThumbnailPath();
            if ($fileinfo->isImage() && $thumbnail_path !== null && file_exists($thumbnail_path)) {
                header('Content-type: ' . $fileinfo->getFiletype());
                readfile($thumbnail_path);
            }
        }
        exit();
    }

    public function showAttachment($attachment_id)
    {
        if ($fileinfo = $this->getTrackerFileInfoFactory()->getById($attachment_id)) {
            if ($fileinfo->fileExists()) {
                $http = Codendi_HTTPPurifier::instance();
                header('X-Content-Type-Options: nosniff');
                header('Content-Type: ' . $http->purify($fileinfo->getFiletype()));
                header('Content-Length: ' . $http->purify($fileinfo->getFilesize()));
                header('Content-Disposition: attachment; filename="' . $http->purify($fileinfo->getFilename()) . '"');
                header('Content-Description: ' . $http->purify($fileinfo->getDescription()));
                if (ob_get_level()) {
                    ob_end_clean();
                }
                flush();
                $file = fopen($fileinfo->getPath(), "r");
                while (! feof($file)) {
                    print fread($file, 30 * 1024);
                    flush();
                }
                fclose($file);
            }
        }
        exit();
    }

    public function getRootPath()
    {
        return $this->getGlobalTrackerRootPath() . $this->getId();
    }

    /**
     * Display the html field in the admin ui
     *
     * @return string html
     */
    protected function fetchAdminFormElement()
    {
        $html  = '';
        $html .= '<div>';
        $html .= '<p>' . dgettext('tuleap-tracker', 'Add a new file:') . '</p>';
        $html .= '<table class="tracker_artifact_add_attachment">';
        $html .= '<tr><td><label>' . dgettext('tuleap-tracker', 'Description:') . '</label></td><td><label>' . dgettext('tuleap-tracker', 'File:') . '</label></td></tr>';
        $html .= '<tr><td><input type="text" id="tracker_field_' . $this->id . '" /></td>';
        $html .= '<td><input type="file" id="tracker_field_' . $this->id . '" /></td></tr>';
        $html .= '</table>';
        $html .= '</div>';
        return $html;
    }

    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker', 'File upload');
    }

    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'Lets the user attach files to the artifact');
    }

    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/attach.png');
    }

    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/attach--plus.png');
    }

    /**
     * Fetch the html code to display the field value in tooltip
     *
     * @param Artifact                    $artifact The artifact
     * @param Tracker_ChangesetValue_File $value    The changeset value of this field
     *
     * @return string The html code to display the field value in tooltip
     */
    protected function fetchTooltipValue(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        $html = '';
        if ($value) {
            $files_info = $value->getFiles();
            if (count($files_info)) {
                $html .= '<div class="cross-ref-tooltip-collection">';

                $hp = Codendi_HTMLPurifier::instance();

                $added = [];
                foreach ($files_info as $file_info) {
                    $add = '';

                    if ($file_info->isImage()) {
                        $query = $this->getFileHTMLPreviewUrl($file_info);
                        $add  .= '<img src="' . $hp->purify($query) . '"
                                      alt="' .  $hp->purify($file_info->getDescription(), CODENDI_PURIFIER_CONVERT_HTML)  . '"
                                 >';
                    } elseif ($file_info->getDescription()) {
                        $add .= '<div class="cross-ref-tooltip-collection-item">';
                        $add .= '<i class="fa fa-paperclip"></i>';
                        $add .= '<p>' . $hp->purify($file_info->getDescription(), CODENDI_PURIFIER_CONVERT_HTML) . '</p>';
                        $add .= '</div>';
                    } else {
                        $add .= '<div class="cross-ref-tooltip-collection-item">';
                        $add .= '<i class="fa fa-paperclip"></i>';
                        $add .= '<p>' . $hp->purify($file_info->getFilename(), CODENDI_PURIFIER_CONVERT_HTML) . '</p>';
                        $add .= '</div>';
                    }
                    $added[] = $add;
                }
                $html .= implode('', $added) . '</div>';
            }
        }
        return $html;
    }

    /**
     * Validate a value
     *
     * @param Artifact $artifact The artifact
     * @param mixed    $value    data coming from the request.
     *
     * @return bool true if the value is considered ok
     */
    protected function validate(Artifact $artifact, $value)
    {
        return true;
    }

    /**
     * Say if the value is valid. If not valid set the internal has_error to true.
     *
     * @param Artifact $artifact The artifact
     * @param mixed    $value    data coming from the request. May be string or array.
     *
     * @return bool true if the value is considered ok
     */
    public function isValid(Artifact $artifact, $value)
    {
        $this->has_errors = false;

        if (is_array($value)) {
            $this->checkAllFilesHaveBeenSuccessfullyUploaded($value);
        }

        return ! $this->has_errors;
    }

    private function checkAllFilesHaveBeenSuccessfullyUploaded($value)
    {
        $rule = new Rule_File();
        foreach ($value as $i => $attachment) {
            if ($this->isAttachmentNeedsToBeValidated($i, $attachment)) {
                if (! $rule->isValid($attachment)) {
                    $this->has_errors = true;
                    $attachment_error = sprintf(dgettext('tuleap-tracker', 'Attachment #%1$s has not been saved:'), $i);
                    $GLOBALS['Response']->addFeedback('error', $attachment_error . ' ' . $rule->getErrorMessage());
                }
            }
        }
    }

    /**
     * @return bool
     */
    private function isAttachmentNeedsToBeValidated($attachment_index, array $attachment)
    {
        if ($attachment_index === 'delete' || isset($attachment['tus-uploaded-id'])) {
            return false;
        }

        $is_file_uploaded             = ! empty($attachment['error']) && $attachment['error'] != UPLOAD_ERR_NO_FILE;
        $is_file_description_provided = trim($attachment['description']);

        return $is_file_uploaded || $is_file_description_provided;
    }

    /**
     * Validate a required field
     *
     * @param Artifact $artifact The artifact to check
     * @param mixed    $value    The submitted value
     *
     * @return bool true on success or false on failure
     */
    public function isValidRegardingRequiredProperty(Artifact $artifact, $value)
    {
        $this->has_errors = false;

        if (
            is_array($value) &&
            $this->isRequired() &&
            ! $this->checkThatAtLeastOneFileIsUploaded($value) &&
            $this->isPreviousChangesetEmpty($artifact, $value)
        ) {
            $this->addRequiredError();
        }

        return ! $this->has_errors;
    }

    /**
     * Check that at least one file is sent
     *
     * @param array $files the files
     *
     * @return bool true if success
     */
    public function checkThatAtLeastOneFileIsUploaded($files)
    {
        $r              = new Rule_File();
        $a_file_is_sent = false;
        foreach ($files as $action => $attachment) {
            if ($a_file_is_sent) {
                break;
            }
            if ((string) $action === 'delete') {
                continue;
            }
            $a_file_is_sent = isset($attachment['tus-uploaded-id']) || $r->isValid($attachment);
        }
        return $a_file_is_sent;
    }

    /**
     * Extract data from request
     * Some fields like files doesn't have their value submitted in POST or GET
     * Let them populate $fields_data[field_id] if needed
     *
     * @param array &$fields_data The user submitted value
     *
     * @return void
     */
    public function augmentDataFromRequest(&$fields_data)
    {
        if (! isset($fields_data[$this->getId()]) || ! is_array($fields_data[$this->getId()])) {
            $fields_data[$this->getId()] = [];
        }
        $files_infos = $this->getSubmittedInfoFromFILES();
        if (isset($files_infos['name'][$this->getId()])) {
            $info_keys = array_keys($files_infos); //name, type, error, ...
            $nb        = count($files_infos['name'][$this->getId()]);
            for ($i = 0; $i < $nb; ++$i) {
                $tab = [];
                foreach ($info_keys as $key) {
                    $tab[$key] = $files_infos[$key][$this->getId()][$i]['file'];
                }
                if (isset($fields_data[$this->getId()][$i])) {
                    $fields_data[$this->getId()][$i] = array_merge($fields_data[$this->getId()][$i], $tab);
                } else {
                    $fields_data[$this->getId()][] = $tab;
                }
            }
        }
    }

    /**
     * Get the array wich contains files submitted by the user
     *
     * @return null|array null if not found
     */
    protected function getSubmittedInfoFromFILES()
    {
        return isset($_FILES['artifact']) ? $_FILES['artifact'] : null;
    }

    protected $files_info_from_request = null;
    /**
     * Extract the file information (name, error, tmp, ...) from the request
     *
     * @return array Array of file info
     */
    protected function extractFilesFromRequest()
    {
        if (! $this->files_info_from_request) {
        }
        return $this->files_info_from_request;
    }

    protected function saveValue(
        $artifact,
        $changeset_value_id,
        $value,
        ?Tracker_Artifact_ChangesetValue $previous_changesetvalue,
        CreatedFileURLMapping $url_mapping,
    ) {
        $mover              = new AttachmentToFinalPlaceMover();
        $rule_file          = new Rule_File();
        $ongoing_upload_dao = new FileOngoingUploadDao();
        $attachment_creator = new AttachmentForTusUploadCreator(
            $this->getFileInfoForTusUploadedFileReadyToBeAttachedProvider($ongoing_upload_dao),
            $ongoing_upload_dao,
            new AttachmentForRestCreator(
                $mover,
                $this->getTemporaryFileManager(),
                new AttachmentForTraditionalUploadCreator($mover, $rule_file),
                $rule_file
            )
        );

        $saver = new ChangesetValueFileSaver($this->getValueDao(), $attachment_creator);

        assert($previous_changesetvalue instanceof Tracker_Artifact_ChangesetValue_File || $previous_changesetvalue === null);
        return $saver->saveValue(
            $this->getCurrentUser(),
            $this,
            $changeset_value_id,
            $value,
            $previous_changesetvalue,
            $url_mapping
        );
    }

    /**
     * @see Tracker_FormElement_Field::hasChanges()
     */
    public function hasChanges(Artifact $artifact, Tracker_Artifact_ChangesetValue $old_value, $new_value)
    {
        //"old" and "new" value are irrelevant in this context.
        //We just have to know if there is at least one file successfully uploaded
        return $this->checkThatAtLeastOneFileIsUploaded($new_value) || ! empty($new_value['delete']);
    }

    /**
     * Tells if the field takes two columns
     * Ugly legacy hack to display fields in columns
     *
     * @return bool
     */
    public function takesTwoColumns()
    {
        return true;
    }

    /**
     * Get the value of this field
     *
     * @param Tracker_Artifact_Changeset $changeset   The changeset (needed in only few cases like 'lud' field)
     * @param int                        $value_id    The id of the value
     * @param bool $has_changed If the changeset value has changed from the rpevious one
     *
     */
    public function getChangesetValue($changeset, $value_id, $has_changed): Tracker_Artifact_ChangesetValue_File
    {
        $file_info_factory = $this->getTrackerFileInfoFactory();

        $files      = [];
        $file_value = $this->getValueDao()->searchById($value_id);
        foreach ($file_value as $row) {
            $file = $file_info_factory->getById($row['fileinfo_id']);
            if ($file !== null) {
                $files[] = $file;
            }
        }
        return new Tracker_Artifact_ChangesetValue_File($value_id, $changeset, $this, $has_changed, $files);
    }

    /**
     * Get the file dao
     *
     * @return Tracker_FileInfoDao
     */
    protected function getFileInfoDao()
    {
        return new Tracker_FileInfoDao();
    }

    /**
     * Get file info factory
     *
     * @return Tracker_FileInfoFactory
     */
    protected function getFileInfoFactory()
    {
        return new Tracker_FileInfoFactory(
            $this->getFileInfoDao(),
            Tracker_FormElementFactory::instance(),
            Tracker_ArtifactFactory::instance()
        );
    }

    /**
     * Get available values of this field for REST usage
     * Fields like int, float, date, string don't have available values
     *
     * @return mixed The values or null if there are no specific available values
     */
    public function getRESTAvailableValues()
    {
        return null;
    }

    /**
     * Override default value as it's not possible to import a file via CSV
     *
     * @param type $csv_value
     *
     * @return array
     */
    public function getFieldDataFromCSVValue($csv_value, ?Artifact $artifact = null)
    {
        return [];
    }

    public function getFieldDataFromRESTValue(array $rest_value, ?Artifact $artifact = null)
    {
        //Transform array to object
        $value = json_decode(json_encode($rest_value), false);

        $this->validateDataFromREST($value);

        $builder = new FieldDataFromRESTBuilder(
            $this->getUserManager(),
            $this->getFormElementFactory(),
            $this->getTrackerFileInfoFactory(),
            $this->getTemporaryFileManager(),
            $this->getFileInfoForTusUploadedFileReadyToBeAttachedProvider(new FileOngoingUploadDao())
        );
        return $builder->buildFieldDataFromREST($value, $this, $artifact);
    }

    public function getFieldDataFromRESTValueByField($value, ?Artifact $artifact = null)
    {
        throw new Tracker_FormElement_RESTValueByField_NotImplementedException();
    }

    private function validateDataFromREST($data)
    {
        if (! property_exists($data, 'value') || ! is_array($data->value)) {
            throw new Tracker_FormElement_InvalidFieldException('Invalid format for file field "' . $data->field_id . '". '
                . ' Correct format is {"field_id" : 425, "value" : [457, 258]}');
        }
    }

    /**
     * @return Tracker_Artifact_Attachment_TemporaryFileManager
     */
    private function getTemporaryFileManager()
    {
        return new Tracker_Artifact_Attachment_TemporaryFileManager(
            $this->getUserManager(),
            new Tracker_Artifact_Attachment_TemporaryFileManagerDao(),
            new System_Command(),
            ForgeConfig::get('sys_file_deletion_delay'),
            new \Tuleap\DB\DBTransactionExecutorWithConnection(\Tuleap\DB\DBFactory::getMainTuleapDBConnection())
        );
    }

    private function getUserManager()
    {
        return UserManager::instance();
    }

    protected function getTrackerFileInfoFactory()
    {
        return new Tracker_FileInfoFactory(
            new Tracker_FileInfoDao(),
            Tracker_FormElementFactory::instance(),
            Tracker_ArtifactFactory::instance()
        );
    }

    protected function getTemporaryFileManagerDao()
    {
        return new Tracker_Artifact_Attachment_TemporaryFileManagerDao();
    }

    public function deleteChangesetValue(Tracker_Artifact_Changeset $changeset, $changeset_value_id)
    {
        $values = $this->getChangesetValue($changeset, $changeset_value_id, false);
        foreach ($values as $fileinfo) {
            $fileinfo->delete();
        }
        parent::deleteChangesetValue($changeset, $changeset_value_id);
    }

    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitFile($this);
    }

    protected function isPreviousChangesetEmpty(Artifact $artifact, $value)
    {
        $last_changeset = $artifact->getLastChangeset();

        if (
            $last_changeset &&
            ! is_a($last_changeset, Tracker_Artifact_Changeset_Null::class) &&
            count($last_changeset->getValue($this)->getFiles()) > 0
        ) {
            return $this->areAllFilesDeletedFromPreviousChangeset($last_changeset, $value);
        }
        return true;
    }

    private function areAllFilesDeletedFromPreviousChangeset($last_changeset, $value)
    {
        $files = $last_changeset->getValue($this)->getFiles();
        if (isset($value['delete']) && (count($files) == count($value['delete']))) {
            return true;
        }
        return false;
    }

    public function isEmpty($value, $artifact)
    {
        $is_empty = ! $this->checkThatAtLeastOneFileIsUploaded($value);
        if ($is_empty) {
            $is_empty = $this->isPreviousChangesetEmpty($artifact, $value);
        }
        return $is_empty;
    }

    /**
     * @return string
     */
    public function getGlobalTrackerRootPath()
    {
        return ForgeConfig::get('sys_data_dir') . '/tracker/';
    }

    protected function getFileInfoForTusUploadedFileReadyToBeAttachedProvider(FileOngoingUploadDao $ongoing_upload_dao): FileInfoForTusUploadedFileReadyToBeAttachedProvider
    {
        return new FileInfoForTusUploadedFileReadyToBeAttachedProvider(
            new FileBeingUploadedInformationProvider(
                new UploadPathAllocator(
                    $ongoing_upload_dao,
                    Tracker_FormElementFactory::instance()
                ),
                $ongoing_upload_dao,
                new RESTCurrentUserMiddleware(\Tuleap\REST\UserManager::build(), new BasicAuthentication()),
            ),
            $ongoing_upload_dao
        );
    }
}
