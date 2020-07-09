<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

namespace Tuleap\User\REST;

use PFUser;
use Tuleap\REST\JsonCast;
use UserHelper;

/**
 * @psalm-immutable
 */
class MinimalUserRepresentation
{

    public const ROUTE = 'users';
    /**
     * @var int | null {@type int}
     */
    public $id;
    /**
     * @var string | null {@type string}
     */
    public $uri;
    /**
     * @var string | null {@type string}
     */
    public $user_url;
    /**
     * @var string | null {@type string}
     */
    public $real_name;
    /**
     * @var String {@type string}
     */
    public $display_name;
    /**
     * @var string | null {@type string}
     */
    public $username;
    /**
     * @var string | null {@type string}
     */
    public $ldap_id;
    /**
     * @var string {@type string}
     */
    public $avatar_url;
    /**
     * @var bool {@type bool}
     */
    public $is_anonymous;
    /**
     * @var bool {@type bool}
     */
    public $has_avatar;

    private function __construct(
        ?int $id,
        ?string $uri,
        ?string $user_url,
        string $display_name,
        ?string $real_name,
        ?string $username,
        ?string $ldap_id,
        string $avatar_url,
        bool $is_anonymous,
        bool $has_avatar
    ) {
        $this->id           = $id;
        $this->uri          = $uri;
        $this->user_url     = $user_url;
        $this->display_name = $display_name;
        $this->real_name    = $real_name;
        $this->username     = $username;
        $this->ldap_id      = $ldap_id;
        $this->avatar_url   = $avatar_url;
        $this->is_anonymous = $is_anonymous;
        $this->has_avatar   = $has_avatar;
    }

    /**
     * @return MinimalUserRepresentation
     */
    public static function build(PFUser $user)
    {
        $user_helper = UserHelper::instance();
        $user_id     = $user->getId();

        return new self(
            ($user->isAnonymous()) ? null : JsonCast::toInt($user_id),
            ($user->isAnonymous()) ? null : UserRepresentation::ROUTE . '/' . $user_id,
            ($user->isAnonymous()) ? null : $user_helper->getUserUrl($user),
            self::getDisplayName($user_helper, $user),
            ($user->isAnonymous()) ? null : $user->getRealName(),
            ($user->isAnonymous()) ? null : $user->getUserName(),
            ($user->isAnonymous()) ? null : $user->getLdapId(),
            $user->getAvatarUrl(),
            (bool) $user->isAnonymous(),
            (bool) $user->hasAvatar(),
        );
    }

    private static function getDisplayName(UserHelper $user_helper, PFUser $user): string
    {
        if (! $user->isAnonymous()) {
            return $user_helper->getDisplayNameFromUser($user) ?? '';
        }

        $email = $user->getEmail();
        if ($email) {
            return $email;
        }

        return _('Anonymous user');
    }
}
