{* This is a text-only email template; do not include HTML! *}
{translate text="This email was sent from"}: {$from}
------------------------------------------------------------

  {$title}
  {translate text="email_link"}: {$url}/WorldCat/Record?id={$recordID|escape:"url"}
------------------------------------------------------------

{if !empty($message)}
{translate text="Message From Sender"}:
{$message}
{/if}
