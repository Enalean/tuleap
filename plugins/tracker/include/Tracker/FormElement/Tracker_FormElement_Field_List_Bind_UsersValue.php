<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Tuleap\User\REST\UserRepresentation;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_FormElement_Field_List_Bind_UsersValue extends Tracker_FormElement_Field_List_BindValue
{
    protected $id;
    protected $user_name;
    protected $display_name;
    private $hp;

    public function __construct($id, $user_name = null, $display_name = null, private readonly ?PFUser $user = null)
    {
        if ($user !== null) {
            parent::__construct($user->getId(), false);
            $this->user_name = $user->getUserName();
        } else {
            parent::__construct($id, false);
            $this->user_name = $user_name;
        }
        $this->display_name = $display_name;
        $this->hp           = Codendi_HTMLPurifier::instance();
    }

    public static function fromUser(PFUser $user, string $full_name): self
    {
        return new self(-1, null, $full_name, $user);
    }

    public function getUsername()
    {
        if ($this->user_name == null) {
            $user            = $this->getUser();
            $this->user_name = $user->getUsername();
        }
        return $this->user_name;
    }

    public function getLabel(): string
    {
        if ($this->display_name) {
            return $this->display_name;
        }
        return $this->getUserHelper()->getDisplayNameFromUserId($this->getId());
    }

    public function getDataset(Tracker_FormElement_Field_List $field): array
    {
        return [
            'data-avatar-url' => $this->getUser()->getAvatarUrl(),
        ];
    }

    protected function getUserHelper()
    {
        return UserHelper::instance();
    }

    public function getUser(): PFUser
    {
        if (isset($this->user)) {
            return $this->user;
        }
        return $this->getUserManager()->getUserById($this->getId());
    }

    protected function getLink()
    {
        $display_name = $this->getLabel();

        return '<a class="link-to-user" href="' . $this->getUserUrl() . '">' .
               $this->hp->purify($display_name, CODENDI_PURIFIER_CONVERT_HTML) .
               '</a>';
    }

    protected function getUserManager()
    {
        return UserManager::instance();
    }

    public function __toString(): string
    {
        return 'Tracker_FormElement_Field_List_Bind_UsersValue #' . $this->getId();
    }

    public function fetchFormatted()
    {
        return $this->getLink();
    }

    public function fetchCard(Tracker_CardDisplayPreferences $display_preferences)
    {
        if ($this->getId() == 100) {
            return '';
        }

        $html = '<div class="card-field-users">';

        $user = $this->getUser();
        if ($display_preferences->shouldDisplayAvatars()) {
            $html .= $user->fetchHtmlAvatar();
        }
        $html .= $this->fetchUserDisplayName($user);

        $html .= '</div>';

        return $html;
    }

    private function fetchUserDisplayName(PFUser $user)
    {
        $user_helper = new UserHelper();
        $name        = $this->hp->purify($user_helper->getDisplayNameFromUser($user));
        $user_id     = $this->getId();

        $html  = '<div class="realname"
                       title="' . $name . '"
                       data-user-id = "' . $user_id . '"
                   >';
        $html .= $name;
        $html .= '</div>';
        return $html;
    }

    public function fetchFormattedForCSV()
    {
        return $this->getUsername();
    }

    /**
     * @see Tracker_FormElement_Field_List_Value::fetchFormattedForJson
     */
    public function fetchFormattedForJson()
    {
        $json = parent::fetchFormattedForJson();

        $json['username']     = $this->getUsername();
        $json['realname']     = $this->getUser()->getRealName();
        $json['avatar_url']   = $this->getUser()->getAvatarUrl();
        $json['display_name'] = UserHelper::instance()->getDisplayNameFromUser($this->getUser());

        return $json;
    }

    public function getAPIValue()
    {
        return $this->getUsername();
    }

    public function getJsonValue()
    {
        if ($this->id == 100) {
            return;
        }
        return $this->id;
    }

    public function getXMLExportLabel()
    {
        return $this->user_name;
    }

    public function getFullRESTValue(Tracker_FormElement_Field $field)
    {
        if ($this->getId() == 100) {
            $user = new PFUser();
        } else {
            $user_manager = UserManager::instance();
            $user         = $user_manager->getUserByUserName($this->getUsername());
        }

        return UserRepresentation::build($user);
    }

    public function getFullRESTValueForAnonymous(Tracker_Artifact_Changeset $changeset)
    {
        $user = new PFUser();
        $user->setEmail($changeset->getEmail());
        $user->setRealName($changeset->getEmail());

        return UserRepresentation::build($user);
    }

    private function getUserUrl()
    {
        $user_name = $this->user_name;
        if (! $user_name) {
            $user_name = $this->getUser()->getUserName();
        }

        return '/users/' . urlencode($user_name);
    }
}
