{* This is a text-only email template; do not include HTML! *}
{translate text="This email was sent from"}: {$from}
------------------------------------------------------------

{if !empty($message)}
{translate text="Message From Sender"}:
{$message}

{/if}
  {translate text="email_link"}: {$msgUrl}
------------------------------------------------------------

