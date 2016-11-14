<?php
/**
 * Copyright (c) STMicroelectronics 2016. All rights reserved
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

class Tracker_FormElement_Field_Encrypted extends Tracker_FormElement_Field_String
{

    /**
     * Fetch the html code to display the field value in new artifact submission form
     * @param array $submitted_values the values already submitted
     *
     * @return string html
     */
    protected function fetchSubmitValue($submitted_values=array())
    {
        $html  = '';
        $value = $this->getValueFromSubmitOrDefault($submitted_values);
        $html_purifier    = Codendi_HTMLPurifier::instance();
        $html .= '<div class="input-append">
                      <input type="password" autocomplete="off" id="password_'. $this->id .'" class="form-control" name="artifact['. $this->id .']" size="'. $this->getProperty('size') .'"
                     '. ($this->getProperty('maxchars') ? 'maxlength="'. $this->getProperty('maxchars') .'"' : '')  .'value= "'.  $html_purifier->purify($value, CODENDI_PURIFIER_CONVERT_HTML)  .'" />
                     <button class="btn" type="button" id="show_password_'. $this->id .'">
                         <span id="show_password_icon_'. $this->id .'" class="icon-eye-close"></span>
                     </button>
                  </div>';

        $html .= '<script type="text/javascript">
                      (function($) {
                          $("#show_password_'. $this->id .'").bind("mousedown", function (event) {
                              $("#password_'. $this->id .'").attr("type", "text");
                              $("#show_password_icon_'. $this->id .'").attr("class", "icon-eye-open");
                          })
                          $("#show_password_'. $this->id .'").bind("mouseup", function (event) {
                              $("#password_'. $this->id .'").attr("type", "password");
                              $("#show_password_icon_'. $this->id .'").attr("class", "icon-eye-close");
                          })
                          $("#show_password_'. $this->id .'").bind("mouseout", function (event) {
                              $("#password_'. $this->id .'").attr("type", "password");
                              $("#show_password_icon_'. $this->id .'").attr("class", "icon-eye-close");
                          })
                      }(jQuery));
                  </script>';
        return $html;
    }

    /**
     * Display the html field in the admin ui
     * @return string html
     */
    protected function fetchAdminFormElement()
    {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        $value = '';
        if ($this->hasDefaultValue()) {
            $value = $this->getDefaultValue();
        }
        $html .= '<input id="password" class="form-control" type="text"
                         size="'. $this->getProperty('size') .'"
                         '. ($this->getProperty('maxchars') ? 'maxlength="'. $this->getProperty('maxchars') .'"' : '')  .'
                         value="'.  $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML) .'" autocomplete="off"/>';
        return $html;
    }

    /**
     * @return the label of the field (mainly used in admin part)
     */
    public static function getFactoryLabel()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_encryption', 'field_label');
    }

    /**
     * @return the description of the field (mainly used in admin part)
     */
    public static function getFactoryDescription()
    {
          return $GLOBALS['Language']->getText('plugin_tracker_encryption', 'field_label');
    }

    /**
     * @return the path to the icon
     */
    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/lock.png');
    }

    /**
     * @return the path to the icon
     */
    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/lock.png');
    }

    protected function validate(Tracker_Artifact $artifact, $value)
    {
        $dao_pub_key        = new TrackerPublicKeyDao();
        $tracker_key        = new Tracker_Key($dao_pub_key, $artifact->tracker_id);
        $key                = $tracker_key->getKey();
        $maximum_characters_allowed = $tracker_key->getFieldSize($key);
        if ($maximum_characters_allowed !== 0 && mb_strlen($value) > $maximum_characters_allowed) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText(
                    'plugin_tracker_common_artifact',
                    'error_string_max_characters',
                    array($this->getLabel(), $maximum_characters_allowed)
                )
            );
            return false;
        }
        return true;
    }

    protected function saveValue($artifact, $changeset_value_id, $value, Tracker_Artifact_ChangesetValue $previous_changesetvalue = null)
    {
        if ($value != "") {
            $dao_pub_key        = new TrackerPublicKeyDao();
            $tracker_key        = new Tracker_Key($dao_pub_key, $artifact->tracker_id);
            try {
                $encryption_manager = new Encryption_Manager($tracker_key);
                return $this->getValueDao()->create($changeset_value_id, $encryption_manager->encrypt($value));
            } catch (Tracker_EncryptionException $exception) {
                return $exception->getMessage();
            }
        } else {
            return $this->getValueDao()->create($changeset_value_id, $value);
        }
    }
}
