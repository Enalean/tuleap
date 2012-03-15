<?xml version="1.0"?>
<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/">
  <ShortName>{$site.title}</ShortName>
  <Description>Library Catalog Search</Description>
  <Image height="16" width="16" type="image/png">{$site.url}/vufind-favicon.png</Image>
  <Contact>{$site.email}</Contact>
  <Url type="text/html" method="get" template="{$site.url}/Search/Results?lookfor={literal}{searchTerms}&amp;page={startPage?}{/literal}"/>
  <Url type="application/rss+xml" method="get" template="{$site.url}/Search/Results?lookfor={literal}{searchTerms}{/literal}&amp;view=rss"/>
  <Url type="application/x-suggestions+json" method="get" template="{$site.url}/Search/Suggest?lookfor={literal}{searchTerms}{/literal}&amp;format=JSON"/>
</OpenSearchDescription>
