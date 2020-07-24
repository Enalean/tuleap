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

class SVN_TokenPresenter
{

    public $id;

    public $generated_date;

    public $last_used_on;

    public $last_used_by;

    public $comment;

    public function __construct(SVN_Token $svn_token)
    {
        $this->id             = $svn_token->getId();
        $this->generated_date = format_date(
            $GLOBALS['Language']->getText('system', 'datefmt'),
            $svn_token->getGeneratedDate()
        );

        $this->setDateForUser($svn_token);

        $this->last_used_by = $svn_token->getLastIp();
        if (! $this->last_used_by) {
            $this->last_used_by = $this->getDefaultUsedBy();
        }

        $this->comment = $svn_token->getComment();
        if (! $this->comment) {
            $this->comment = $this->getDefaultComment();
        }
    }

    private function setDateForUser(SVN_Token $svn_token)
    {
        if (! $svn_token->getLastUsage()) {
            $this->last_used_on = $this->getDefaultLastUsedOn();
        } else {
            $this->last_used_on = format_date(
                $GLOBALS['Language']->getText('system', 'datefmt'),
                $svn_token->getLastUsage()
            );
        }
    }

    private function getDefaultLastUsedOn()
    {
        return $GLOBALS['Language']->getText('svn_tokens', 'default_last_used_on');
    }

    private function getDefaultUsedBy()
    {
        return $GLOBALS['Language']->getText('svn_tokens', 'default_last_used_by');
    }

    private function getDefaultComment()
    {
        return $GLOBALS['Language']->getText('svn_tokens', 'default_comment', [$this->generated_date]);
    }
}
