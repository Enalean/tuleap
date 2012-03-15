<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="en">
  <head>
    <title>Library Resource Finder: {$pageTitle}</title>
    {css media="screen" filename="styles.css"}
    {css media="print" filename="print.css"}
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
    <script language="JavaScript" type="text/javascript">
      path = '{$path}';
    </script>
  </head>

  <body>

    <div align="center">
      <h2>{translate text="System Unavailable"}</h2>
      <p>
        {translate text="The system is currently unavailable due to system maintenance"}.
        {translate text="Please check back soon"}.
      </p>
      <p>
        {translate text="Please contact the Library Reference Department for assistance"}<br>
        <a href="mailto:{$supportEmail}">{$supportEmail}</a>
      </p>
    </div>
    
  </body>
</html>