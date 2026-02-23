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

namespace Tuleap\Tracker\FormElement\Field\List\Bind\User;

use Codendi_HTMLPurifier;
use PFUser;
use Tracker_Artifact_Changeset;
use Tracker_CardDisplayPreferences;
use Tracker_FormElement_Field_List_BindValue;
use Tuleap\Tracker\FormElement\Field\List\ListField;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\User\Avatar\AvatarHashDao;
use Tuleap\User\Avatar\UserAvatarUrl;
use Tuleap\User\Avatar\UserAvatarUrlProvider;
use Tuleap\User\REST\UserRepresentation;
use UserHelper;
use UserManager;

class ListFieldUserBindValue extends Tracker_FormElement_Field_List_BindValue
{
    protected $id;
    protected $user_name;
    protected $display_name;
    private $hp;

    public function __construct($id, $user_name = null, $display_name = null, private readonly ?UserAvatarUrl $user_with_avatar = null)
    {
        $user = $this->user_with_avatar?->user;
        if ($user !== null) {
            parent::__construct((int) $user->getId(), false);
            $this->user_name = $user->getUserName();
        } else {
            parent::__construct($id, false);
            $this->user_name = $user_name;
        }
        $this->display_name = $display_name;
        $this->hp           = Codendi_HTMLPurifier::instance();
    }

    public static function fromUser(UserAvatarUrl $user, string $full_name): self
    {
        return new self(-1, null, $full_name, $user);
    }

    public static function fromId(int $id): self
    {
        return new self($id);
    }

    public function getUsername()
    {
        if ($this->user_name == null) {
            $user            = $this->getUser();
            $this->user_name = $user->getUsername();
        }
        return $this->user_name;
    }

    #[\Override]
    public function getLabel(): string
    {
        if ($this->display_name) {
            return $this->display_name;
        }
        return $this->getUserHelper()->getDisplayNameFromUserId($this->getId());
    }

    private function getUserAvatarURL(): string
    {
        if ($this->user_with_avatar !== null) {
            return $this->user_with_avatar->avatar_url;
        }
        return $this->getUser()->getAvatarUrl();
    }

    #[\Override]
    public function getDataset(ListField $field): array
    {
        return [
            'data-avatar-url' => $this->getUserAvatarURL(),
        ];
    }

    protected function getUserHelper()
    {
        return UserHelper::instance();
    }

    public function getUser(): PFUser
    {
        if ($this->user_with_avatar !== null) {
            return $this->user_with_avatar->user;
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

    #[\Override]
    public function fetchFormatted()
    {
        return $this->getLink();
    }

    public function fetchCard(Tracker_CardDisplayPreferences $display_preferences): string
    {
        if ($this->getId() == 100) {
            return '';
        }

        $html = '<div class="card-field-users">';

        $user = $this->getUser();
        if ($display_preferences->shouldDisplayAvatars()) {
            $html .= $user->fetchHtmlAvatar($this->user_with_avatar);
        }
        $html .= $this->fetchUserDisplayName();

        $html .= '</div>';

        return $html;
    }

    private function fetchUserDisplayName(): string
    {
        $name    = $this->hp->purify($this->getLabel());
        $user_id = $this->hp->purify($this->getId());

        $html  = '<div class="realname"
                       title="' . $name . '"
                       data-user-id = "' . $user_id . '"
                   >';
        $html .= $name;
        $html .= '</div>';
        return $html;
    }

    #[\Override]
    public function fetchFormattedForCSV()
    {
        return $this->getUsername();
    }

    /**
     * @see Tracker_FormElement_Field_List_Value::fetchFormattedForJson
     */
    #[\Override]
    public function fetchFormattedForJson()
    {
        $json = parent::fetchFormattedForJson();

        $json['username']     = $this->getUsername();
        $json['realname']     = $this->getUser()->getRealName();
        $json['avatar_url']   = $this->getUserAvatarURL();
        $json['display_name'] = $this->getLabel();

        return $json;
    }

    #[\Override]
    public function getAPIValue()
    {
        return $this->getUsername();
    }

    #[\Override]
    public function getJsonValue()
    {
        if ($this->id == 100) {
            return;
        }
        return $this->id;
    }

    #[\Override]
    public function getFullRESTValue(TrackerField $field)
    {
        if ($this->getId() == 100) {
            $user = new PFUser();
        } else {
            $user_manager = UserManager::instance();
            $user         = $user_manager->getUserByUserName($this->getUsername());
        }

        return UserRepresentation::build($user, new UserAvatarUrlProvider(new AvatarHashDao()));
    }

    public function getFullRESTValueForAnonymous(Tracker_Artifact_Changeset $changeset)
    {
        $user = new PFUser();
        $user->setEmail($changeset->getEmail());
        $user->setRealName($changeset->getEmail());

        return UserRepresentation::build($user, new UserAvatarUrlProvider(new AvatarHashDao()));
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
