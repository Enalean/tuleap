<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class Account_ConfirmationPresenter {

    public $title;
    public $content;
    public $thanks;
    public $is_thanks;
    public $redirect_url;
    public $redirect_content;
    public $email_is_sent;
    public $email_no_send;
    public $displayedImage;
    public $image_url;
    public $email_presenter;

    public function __construct(
            $title,
            $content,
            $thanks,
            $is_thanks,
            $redirect_url,
            $redirect_content,
            $email_is_sent,
            $email_no_send,
            $displayedImage,
            $image_url,
            $email_presenter
    ) {
        $this->title            = $title;
        $this->content          = $content;
        $this->thanks           = $thanks;
        $this->is_thanks        = $is_thanks;
        $this->redirect_url     = $redirect_url;
        $this->redirect_content = $redirect_content;
        $this->email_is_sent    = $email_is_sent;
        $this->email_no_send    = $email_no_send;
        $this->displayedImage   = $displayedImage;
        $this->image_url        = $image_url;
        $this->email_presenter  = $email_presenter;
    }
}
