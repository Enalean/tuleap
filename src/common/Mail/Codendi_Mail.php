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

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Part\DataPart;
use Tuleap\Mail\Transport\MailTransportBuilder;
use Tuleap\Mail\MailLogger;

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

    private \Symfony\Component\Mime\Email $message;

    /**
     * @var Tuleap_Template_Mail
     */
    protected $look_and_feel_template;

    /**
     * @var Mail_RecipientListBuilder
     */
    private $recipient_list_builder;

    private MailerInterface $mailer;

    private string $body_text = '';

    private string $body_html = '';

    /**
     * @psalm-var list<array{'data': string, 'filename': string, 'mime_type': string}>
     */
    private array $attachments = [];

    /**
     * @psalm-var list<array{'data': string, 'cid': string, 'mime_type': string}>
     */
    private array $inline_attachments = [];

    public function __construct()
    {
        $this->message                = new \Symfony\Component\Mime\Email();
        $this->recipient_list_builder = new Mail_RecipientListBuilder(UserManager::instance());
        $this->logger                 = new MailLogger();
        $this->mailer                 = MailTransportBuilder::buildMailTransport($this->logger);
    }

    public function setMessageId($message_id)
    {
        $message_headers = $this->message->getHeaders();
        $message_headers->remove('Message-ID');
        $message_headers->addIdHeader('Message-ID', (string) $message_id);
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
            $addresses = match ($recipient_type) {
                'To' => $this->message->getTo(),
                'Cc' => $this->message->getCc(),
                'Bcc' => $this->message->getBcc(),
            };
            $emails = [];
            foreach ($addresses as $address) {
                $emails[] = $address->getAddress();
            }
            return implode(', ', $emails);
        }
        return '';
    }

    public function setFrom($email)
    {
        list($email, $name) = $this->cleanupMailFormat($email);
        $this->message->getHeaders()->remove('From');
        $this->message->addFrom(new Address($email, $name));
    }

    public function clearFrom()
    {
        $this->message->getHeaders()->remove('From');
    }

    public function setSubject($subject)
    {
        $this->message->subject($subject);
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
        $this->message->addTo(new Address($email, $name));
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
        $this->message->addBcc(new Address($email, $name));
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
        $this->message->addCc(new Address($email, $name));
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
            $this->message->addTo(new Address($to['email'], $to['real_name']));
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
            $this->message->addBcc(new Address($user['email'], $user['real_name']));
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



        if ($this->body_text !== '') {
            $this->message->text($this->body_text);
        }
        if ($this->body_html !== '') {
            $this->message->html($this->body_html);
        }
        foreach ($this->attachments as $attachment) {
            $this->message->attach($attachment['data'], $attachment['filename'], $attachment['mime_type']);
        }
        foreach ($this->inline_attachments as $attachment) {
            $data_part = (new DataPart($attachment['data'], null, $attachment['mime_type']))->asInline();
            $data_part->setContentId($attachment['cid']);
        }
        if (count($this->message->getTo()) === 0) {
            $this->setTo(ForgeConfig::get('sys_noreply'), true);
        }
        \Tuleap\Mail\MailInstrumentation::increment();
        try {
            $this->mailer->send($this->message);
        } catch (Exception $e) {
            $status = false;
            \Tuleap\Mail\MailInstrumentation::incrementFailure();
            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('global', 'mail_failed', ForgeConfig::get('sys_email_admin')), CODENDI_PURIFIER_DISABLED);
            $this->logger->error('Mail notification failed', ['exception' => $e]);

            $to_addresses = $this->message->getTo();
            if (count($to_addresses) > 0) {
                $emails = [];
                foreach ($to_addresses as $address) {
                    $emails[] = $address->getAddress();
                }
                $this->logger->debug('Message sent to: ' . implode(',', $emails));
            } else {
                $this->logger->error("No 'to' found");
            }
        }
        $this->clearRecipients();
        return $status;
    }

    private function clearRecipients(): void
    {
        $headers = $this->message->getHeaders();
        $headers->remove('To');
        $headers->remove('Cc');
        $headers->remove('Bcc');
    }

    public function addAdditionalHeader($name, $value): void
    {
        $this->message->getHeaders()->addHeader($name, $value);
    }

    public function addInlineAttachment($data, $mime_type, $cid): void
    {
        $this->inline_attachments[] = ['data' => $data, 'cid' => $cid, 'mime_type' => $mime_type];
    }

    public function addAttachment(string $data, string $filename, string $mime_type): void
    {
        $this->attachments[] = ['data' => $data, 'filename' => $filename, 'mime_type' => $mime_type];
    }
}
