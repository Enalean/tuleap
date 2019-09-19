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

class SVN_Token
{

    /**
     * @var PFUser
     */
    private $user;

    private $id;

    private $token;

    private $generated_date;

    private $last_usage;

    private $last_ip;

    private $comment;


    public function __construct(
        PFUser $user,
        $id,
        $token,
        $generated_date,
        $last_usage,
        $last_ip,
        $comment
    ) {
        $this->id             = $id;
        $this->user           = $user;
        $this->token          = $token;
        $this->generated_date = $generated_date;
        $this->last_usage     = $last_usage;
        $this->last_ip        = $last_ip;
        $this->comment        = $comment;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function getGeneratedDate()
    {
        return $this->generated_date;
    }

    public function getLastUsage()
    {
        return $this->last_usage;
    }

    public function getLastIp()
    {
        return $this->last_ip;
    }

    public function getComment()
    {
        return $this->comment;
    }
}
