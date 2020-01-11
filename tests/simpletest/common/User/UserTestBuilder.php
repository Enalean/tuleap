<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

/**
 * Ease creation of User object
 *
 * $user = aUser()->withId(123)->withUserName('pouet')->build();
 *
 * @return \UserTestBuilder
 */
function aUser()
{
    return new UserTestBuilder();
}

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class UserTestBuilder
{
    private $params = array('language_id' => 'en_US');
    private $language;

    public function withUserName($name)
    {
        $this->params['user_name'] = $name;
        return $this;
    }

    public function withRealName($realname)
    {
        $this->params['realname'] = $realname;
        return $this;
    }

    public function withEmail($email)
    {
        $this->params['email'] = $email;
        return $this;
    }

    public function withId($id)
    {
        $this->params['user_id'] = $id;
        return $this;
    }

    public function withAuthorizedKeysArray(array $keys)
    {
        $this->params['authorized_keys'] = implode(PFUser::SSH_KEY_SEPARATOR, $keys);
        return $this;
    }

    public function withUnixStatus($status)
    {
        $this->params['unix_status'] = $status;
        return $this;
    }

    public function withLdapId($id)
    {
        $this->params['ldap_id'] = $id;
        return $this;
    }

    public function withPassword($hashed_password)
    {
        $password_handler         = PasswordHandlerFactory::getPasswordHandler();
        $this->params['password'] = $password_handler->computeHashPassword($hashed_password);
        $this->params['user_pw']  = md5($hashed_password);
        return $this;
    }

    public function withStatus($status)
    {
        $this->params['status'] = $status;
        return $this;
    }

    public function withLastPasswordUpdate($timestamp)
    {
        $this->params['last_pwd_update'] = $timestamp;
        return $this;
    }

    public function withLang($lang)
    {
        $this->params['language_id'] = $lang;
        return $this;
    }

    public function withLanguage(BaseLanguage $language)
    {
        $this->language = $language;
        return $this;
    }

    public function build()
    {
        $user = new PFUser($this->params);
        if ($this->language !== null) {
            $user->setLanguage($this->language);
        }
        return $user;
    }
}
