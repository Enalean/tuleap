<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Test\Builders;

class UserTestBuilder
{
    private $params = ['language_id' => 'en_US'];
    /**
     * @var \BaseLanguage
     */
    private $language;

    public static function aUser(): self
    {
        return new self();
    }

    public static function anAnonymousUser(): self
    {
        return (new self())->withId(0);
    }

    public function withUserName(string $name): self
    {
        $this->params['user_name'] = $name;
        return $this;
    }

    public function withRealName(string $string): self
    {
        $this->params['realname'] = $string;
        return $this;
    }

    public function withId(int $id): self
    {
        $this->params['user_id'] = $id;
        return $this;
    }

    public function withLdapId(string $id): self
    {
        $this->params['ldap_id'] = $id;
        return $this;
    }

    public function withLastPwdUpdate(string $timestamp): self
    {
        $this->params['last_pwd_update'] = $timestamp;
        return $this;
    }

    public function withLanguage(\BaseLanguage $language): self
    {
        $this->language = $language;
        return $this;
    }

    public function withAddDate(int $timestamp): self
    {
        $this->params['add_date'] = (string) $timestamp;
        return $this;
    }

    public function withEmail(string $email): self
    {
        $this->params['email'] = (string) $email;
        return $this;
    }

    public function withTimezone(string $timezone): self
    {
        $this->params['timezone'] = $timezone;
        return $this;
    }

    /**
     * @psalm-param \PFUser::STATUS_*
     */
    public function withStatus(string $status): self
    {
        $this->params['status'] = $status;
        return $this;
    }

    public function withLocale(string $language_tag): self
    {
        $this->params['language_id'] = $language_tag;
        return $this;
    }

    public function build(): \PFUser
    {
        $user = new \PFUser($this->params);
        if ($this->language) {
            $user->setLanguage($this->language);
        }
        return $user;
    }
}
