<?php
/**
 * Copyright 1999-2000 (c) The SourceForge Crew
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

require_once __DIR__ . '/../include/pre.php';

$request = HTTPRequest::instance();
$request->checkUserIsSuperUser();

$csrf = new CSRFSynchronizerToken('/admin/massmail.php');
$csrf->check();

$request = HTTPRequest::instance();
if ($request->isPost() && $request->existAndNonEmpty('destination')) {
    $validDestination = new Valid_WhiteList(
        'destination',
        array('preview', 'comm', 'sf', 'all', 'admin', 'sfadmin', 'devel')
    );
    $destination      = $request->getValidated('destination', $validDestination);

    $validFormat = new Valid_WhiteList('comment_format', array('html', 'text'));
    $bodyFormat  = $request->getValidated('comment_format', $validFormat, 'text');

    $mailMessage  = '';
    $validMessage = new Valid_Text('mail_message');
    if ($request->valid($validMessage)) {
        $mailMessage = $request->get('mail_message');
    }

    $mailSubject  = '';
    $validSubject = new Valid_String('mail_subject');
    if ($request->valid($validSubject)) {
        $mailSubject = $request->get('mail_subject');
    }

    if ($destination != 'preview') {
        $event_manager = EventManager::instance();
        $event_manager->processEvent(
            Event::MASSMAIL,
            array(
                'destination' => $destination,
                'message'     => $mailMessage,
                'subject'     => $mailSubject
            )
        );
        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            $GLOBALS['Language']->getText('admin_massmail', 'massmail_queued', '/admin/system_events/?queue=default'),
            CODENDI_PURIFIER_LIGHT
        );
        $GLOBALS['Response']->redirect('/admin/massmail.php');
    } else {
        $purifier = Codendi_HTMLPurifier::instance();
        $title    = $purifier->purify($mailSubject, CODENDI_PURIFIER_CONVERT_HTML);

        $mail = new Codendi_Mail();
        $mail->setFrom(ForgeConfig::get('sys_noreply'));
        $mail->getLookAndFeelTemplate()->set('title', $title);
        $mail->setBodyHtml($mailMessage);
        $mail->setSubject($mailSubject);

        // This part would send a preview email, parameters are retrieved within the function sendPreview() in MassMail.js
        $validMails = array();
        $addresses  = array_filter(
            array_map('trim', preg_split('/[,;]/', $request->get('preview-destination-external')))
        );
        if (is_array($request->get('preview-destination'))) {
            foreach ($request->get('preview-destination') as $identity) {
                $user = $user_manager->findUser($identity);
                if ($user && $user->getEmail()) {
                    $addresses[] = $user->getEmail();
                }
            }
        }

        $rule = new Rule_Email();
        $um   = UserManager::instance();
        foreach ($addresses as $address) {
            if ($rule->isValid($address)) {
                $validMails[] = $address;
            } else {
                $user = $um->findUser($address);
                if ($user) {
                    $address = $user->getEmail();
                    if ($address) {
                        $validMails[] = $address;
                    }
                }
            }
        }
        $previewDestination = implode(', ', $validMails);
        if (! $previewDestination) {
            $GLOBALS['Response']->sendJSON(
                array(
                    'success' => false,
                    'message' => $GLOBALS['Language']->getText('admin_massmail_execute', 'no_mails')
                )
            );
            exit;
        }
        $mail->setTo($previewDestination);

        if ($mail->send()) {
            $GLOBALS['Response']->sendJSON(
                array(
                    'success' => true,
                    'message' => $GLOBALS['Language']->getText('admin_massmail_execute', 'sending')
                )
            );
        } else {
            $GLOBALS['Response']->sendJSON(
                array(
                    'success' => false,
                    'message' => $GLOBALS['Language']->getText('admin_massmail_execute', 'no_sending')
                )
            );
        }
    }
}
