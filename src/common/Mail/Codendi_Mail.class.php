<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2004-2011. All rights reserved
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

use Tuleap\Mail\Transport\MailTransportBuilder;
use Tuleap\Mail\MailLogger;
use Laminas\Mail;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Mime;
use Laminas\Mime\Part as MimePart;

/**
 * It allows to send mails in html format
 *
 */

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Codendi_Mail implements Codendi_Mail_Interface
{
    /**
     * @const Use the common look and feel
     *
     * The common look and feel is the pretty one you can see in trackers v3
     */
    public const USE_COMMON_LOOK_AND_FEEL = true;

    /**
     * @const DO NOT use the common look and feel
     */
    public const DISCARD_COMMON_LOOK_AND_FEEL = false;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var Mail\Message
     */
    private $message;

    /**
     * @var Tuleap_Template_Mail
     */
    protected $look_and_feel_template;

    /**
     * @var Mail_RecipientListBuilder
     */
    private $recipient_list_builder;

    /**
     * @var Mail\Transport\TransportInterface
     */
    private $transport;

    /**
     * @var string
     */
    private $body_text = '';

    /**
     * @var string
     */
    private $body_html = '';

    /**
     * @var MimePart[]
     */
    private $attachments = [];

    /**
     * @var MimePart[]
     */
    private $inline_attachments = [];

    public function __construct()
    {
        $this->message = new Mail\Message();
        $this->message->setEncoding('UTF-8');
        $this->recipient_list_builder = new Mail_RecipientListBuilder(UserManager::instance());
        $this->logger                 = new MailLogger();
        $this->transport              = MailTransportBuilder::buildMailTransport($this->logger);
    }

    public function setMessageId($message_id)
    {
        $message_id_header = new Mail\Header\MessageId();
        $message_id_header->setId($message_id);
        $this->message->getHeaders()->removeHeader($message_id_header->getFieldName());
        $this->message->getHeaders()->addHeader($message_id_header);
    }

    /**
     * Check if given user is autorised to get mails (Ie. Active or Restricted) user.
     *
     * @param Array of users $recipients
     *
     * @return Array of user_name and mail
     */
    private function validateArrayOfUsers(array $users)
    {
        return $this->recipient_list_builder->getValidRecipientsFromUsers($users);
    }

    /**
     * Given a standard email definition, split the name and the email address
     *
     * "name" <email> gives array(email, name).
     * if doesn't match, assume it's only email.
     *
     * @param String $mail
     *
     * @return Array
     */
    public function cleanupMailFormat($mail)
    {
        $pattern = '/(.*)<(.*)>/';
        if (preg_match($pattern, $mail, $matches)) {
            // Remove extra spaces and quotes
            $name = trim(trim($matches[1]), '"\'');
            return [$matches[2], $name];
        } else {
            return [$mail, ''];
        }
    }

    /**
     * Check if given mail/user_name is valid (Ie. Active or Restricted) user.
     *
     * @param list of emails/user_name $mailList
     *
     * @return string[] of real_name and mail
     */
    private function validateCommaSeparatedListOfAddresses($comma_separeted_addresses)
    {
        return $this->recipient_list_builder->getValidRecipientsFromAddresses(preg_split('/[;,]/D', $comma_separeted_addresses));
    }

    /**
     * Return list of mail addresses separated by comma, from the headers, depending on the type
     *
     * @param String $recipient_type Allowed values are "To", "Cc" and "Bcc"
     *
     * @return String
     */
    private function getRecipientsFromHeader($recipient_type)
    {
        $allowed = ['To', 'Cc', 'Bcc'];
        if (in_array($recipient_type, $allowed)) {
            $headers          = $this->message->getHeaders();
            $recipient_header = $headers->get($recipient_type);
            if ($recipient_header !== false) {
                $list_addresses = $recipient_header->getAddressList();
                $addresses      = [];
                foreach ($list_addresses as $address) {
                    $addresses[] = $address->getEmail();
                }
                return implode(', ', $addresses);
            }
        }
        return '';
    }

    public function setFrom($email)
    {
        list($email, $name) = $this->cleanupMailFormat($email);
        $this->message->setFrom($email, $name);
    }

    public function clearFrom()
    {
        $this->message->setFrom([]);
    }

    public function setSubject($subject)
    {
        $this->message->setSubject($subject);
    }

    public function getSubject()
    {
        return $this->message->getSubject();
    }

    /**
     *
     * @param String  $to
     * @param bool $raw
     */
    public function setTo($to, $raw = false)
    {
        list($to,) = $this->cleanupMailFormat($to);
        if (! $raw) {
            $to = $this->validateCommaSeparatedListOfAddresses($to);
            if (! empty($to)) {
                foreach ($to as $row) {
                    $this->addTo($row['email'], $row['real_name']);
                }
            }
        } else {
            $this->addTo($to, '');
        }
    }

    private function addTo($email, $name)
    {
        $email = trim($email);
        if ($email === '') {
            return;
        }
        $this->message->addTo($email, $name);
    }

    /**
     * Return list of destination mail addresses separated by comma
     *
     * @return String
     */
    public function getTo()
    {
        return $this->getRecipientsFromHeader('To');
    }

    /**
     *
     * @param String  $bcc
     * @param bool $raw
     */
    public function setBcc($bcc, $raw = false)
    {
        if (! $raw) {
            $bcc = $this->validateCommaSeparatedListOfAddresses($bcc);
            if (! empty($bcc)) {
                foreach ($bcc as $row) {
                    $this->addBcc($row['email'], $row['real_name']);
                }
            }
        } else {
            $this->addBcc($bcc, '');
        }
    }

    private function addBcc($email, $name)
    {
        $email = trim($email);
        if ($email === '') {
            return;
        }
        $this->message->addBcc($email, $name);
    }

    /**
     * Return list of mail addresses in BCC separated by comma
     *
     * @return String
     */
    public function getBcc()
    {
        return $this->getRecipientsFromHeader('Bcc');
    }

    /**
     *
     * @param String  $cc
     * @param bool $raw
     */
    public function setCc($cc, $raw = false)
    {
        if (! $raw) {
            $cc = $this->validateCommaSeparatedListOfAddresses($cc);
            if (! empty($cc)) {
                foreach ($cc as $row) {
                    $this->addCc($row['email'], $row['real_name']);
                }
            }
        } else {
            $this->addCc($cc, '');
        }
    }

    private function addCc($email, $name)
    {
        $email = trim($email);
        if ($email === '') {
            return;
        }
        $this->message->addCc($email, $name);
    }

    /**
     * Return list of mail addresses in CC separated by comma
     *
     * @return String
     */
    public function getCc()
    {
        return $this->getRecipientsFromHeader('Cc');
    }

    /**
     * @param String $message
     */
    public function setBodyText($message)
    {
        $this->body_text = $message;
    }

    /**
     * Returns the text part of the body mail
     *
     * @return String
     */
    public function getBodyText()
    {
        return $this->body_text;
    }

    /**
     * @return null|MimePart
     */
    private function getTextPart()
    {
        if ($this->body_text === '') {
            return null;
        }
        $text_part           = new MimePart($this->body_text);
        $text_part->type     = Mime::TYPE_TEXT;
        $text_part->encoding = Mime::ENCODING_QUOTEDPRINTABLE;
        $text_part->charset  = 'UTF-8';

        return $text_part;
    }

    /**
     * Set hte template to use for look and feel in html mails
     *
     * @param Tuleap_Template_Mail $tpl The template instance
     *
     * @return void
     */
    public function setLookAndFeelTemplate(Tuleap_Template_Mail $tpl)
    {
        $this->look_and_feel_template = $tpl;
    }

    /**
     * Get the template to use for look and feel in html mails
     *
     * @return Tuleap_Template_Mail
     */
    public function getLookAndFeelTemplate()
    {
        if (! $this->look_and_feel_template) {
            $this->look_and_feel_template = new Tuleap_Template_Mail();
        }
        return $this->look_and_feel_template;
    }

    /**
     * Set the html body part.
     *
     * The default is to send it through the use of a template to send pretty html
     * email in a common format shared across the platform. Some usages require
     * to not use this template it can be discarded with the
     * second parameter $use_common_look_and_feel.
     *
     * @param String $message                  html code to send to the user
     * @param bool   $use_common_look_and_feel self::USE_COMMON_LOOK_AND_FEEL | self::DISCARD_COMMON_LOOK_AND_FEEL (default is USE)
     *
     * @return void
     */
    public function setBodyHtml($message, $use_common_look_and_feel = self::USE_COMMON_LOOK_AND_FEEL)
    {
        if (self::USE_COMMON_LOOK_AND_FEEL == $use_common_look_and_feel) {
            $tpl = $this->getLookAndFeelTemplate();
            $tpl->set('body', $message);
            $message = $tpl->fetch();
        }
        $this->body_html = $message;
    }

    /**
     * Returns the Html part of the body mail
     *
     * @return String
     */
    public function getBodyHtml()
    {
        return $this->body_html;
    }

    /**
     * @return null|MimePart
     */
    private function getHtmlPart()
    {
        if ($this->body_html === '') {
            return null;
        }
        $html_code_part           = new MimePart($this->body_html);
        $html_code_part->type     = Mime::TYPE_HTML;
        $html_code_part->encoding = Mime::ENCODING_QUOTEDPRINTABLE;
        $html_code_part->charset  = 'UTF-8';

        if (empty($this->inline_attachments)) {
            return $html_code_part;
        }

        $html_message = new MimeMessage();
        $html_message->addPart($html_code_part);
        foreach ($this->inline_attachments as $attachment) {
            $html_message->addPart($attachment);
        }
        $html_part           = new MimePart($html_message->generateMessage());
        $html_part->type     = Laminas\Mime\Mime::MULTIPART_RELATED;
        $html_part->boundary = $html_message->getMime()->boundary();

        return $html_part;
    }

    /**
     * @param String $body
     */
    public function setBody($body)
    {
        $this->setBodyHtml($body);
    }

    /**
     * Return the mail body
     *
     * @return String
     */
    public function getBody()
    {
        return $this->getBodyHtml();
    }

    /**
     *
     * @param array of User $to
     *
     * @return array
     */
    public function setToUser($to)
    {
        $arrayTo         = $this->validateArrayOfUsers($to);
        $arrayToRealName = [];
        foreach ($arrayTo as $to) {
            $this->message->addTo($to['email'], $to['real_name']);
            $arrayToRealName[] = $to['real_name'];
        }
        return $arrayToRealName;
    }

    /**
     *
     * @param array of User $bcc
     *
     * @return array
     */
    public function setBccUser($bcc)
    {
        $arrayBcc         = $this->validateArrayOfUsers($bcc);
        $arrayBccRealName = [];
        foreach ($arrayBcc as $user) {
            $this->message->addBcc($user['email'], $user['real_name']);
            $arrayBccRealName[] = $user['real_name'];
        }
        return $arrayBccRealName;
    }

    /**
     * Send the mail
     *
     * @return bool
     */
    public function send()
    {
        $status = true;

        $mime_message = new MimeMessage();
        $mime_message->addPart($this->getBodyPart());
        foreach ($this->attachments as $attachment) {
            $mime_message->addPart($attachment);
        }
        $this->message->setBody($mime_message);
        if (count($this->message->getTo()) === 0) {
            $this->setTo(ForgeConfig::get('sys_noreply'), true);
        }
        \Tuleap\Mail\MailInstrumentation::increment();
        try {
            $this->transport->send($this->message);
        } catch (Exception $e) {
            $status = false;
            \Tuleap\Mail\MailInstrumentation::incrementFailure();
            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('global', 'mail_failed', ForgeConfig::get('sys_email_admin')), CODENDI_PURIFIER_DISABLED);
            $this->logger->error("Mail notification failed", ['exception' => $e]);

            $to_header = $this->message->getHeaders()->get('to');
            if ($to_header) {
                $list      = $to_header->getAddressList();
                $addresses = [];
                foreach ($list as $address) {
                    $addresses[] = $address->getEmail();
                }
                $addresses = implode(',', $addresses);
                $this->logger->debug("Message sent to: $addresses");
            } else {
                $this->logger->error("No 'to' found");
            }
        }
        $this->clearRecipients();
        return $status;
    }

    private function getBodyPart()
    {
        $text_part = $this->getTextPart();
        $html_part = $this->getHtmlPart();

        if ($text_part === null && $html_part !== null) {
            return $html_part;
        }

        if ($text_part !== null && $html_part === null) {
            return $text_part;
        }

        $body_message = new MimeMessage();
        if ($text_part !== null) {
            $body_message->addPart($text_part);
        }
        if ($html_part !== null) {
            $body_message->addPart($html_part);
        }
        $body_part           = new MimePart($body_message->generateMessage());
        $body_part->type     = Laminas\Mime\Mime::MULTIPART_ALTERNATIVE;
        $body_part->boundary = $body_message->getMime()->boundary();

        return $body_part;
    }

    private function clearRecipients()
    {
        $this->message->setTo([]);
        $this->message->setCc([]);
        $this->message->setBcc([]);
    }

    public function addAdditionalHeader($name, $value)
    {
        $header = new Mail\Header\GenericHeader($name, $value);
        $this->message->getHeaders()->addHeader($header);
    }

    public function addInlineAttachment($data, $mime_type, $cid)
    {
        $mime_part                  = $this->getMimePartAttachment($data, $mime_type);
        $mime_part->id              = $cid;
        $mime_part->disposition     = Laminas\Mime\Mime::DISPOSITION_INLINE;
        $this->inline_attachments[] = $mime_part;
    }

    public function addAttachment(string $data, string $filename, string $mime_type): void
    {
        $mime_part              = $this->getMimePartAttachment($data, $mime_type);
        $mime_part->disposition = Laminas\Mime\Mime::DISPOSITION_ATTACHMENT;
        $mime_part->filename    = $filename;

        $this->attachments[] = $mime_part;
    }

    private function getMimePartAttachment($data, $mime_type)
    {
        $mime_part           = new MimePart($data);
        $mime_part->type     = $mime_type;
        $mime_part->encoding = Laminas\Mime\Mime::ENCODING_BASE64;

        return $mime_part;
    }
}
