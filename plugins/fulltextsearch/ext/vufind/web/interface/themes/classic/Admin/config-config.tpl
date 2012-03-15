<!-- $Id: config-config.tpl 1722 2009-10-28 19:28:57Z demiankatz $ -->
<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">

      <div class="yui-gf resulthead">
        {include file="Admin/menu.tpl"}
        <div class="yui-u">
          <h1>VuFind Configuration</h1>
          {include file="Admin/savestatus.tpl"}

          <form method="post">
            <table class="citation">
              <tr>
                <th colspan="2" style="font-weight: bold; border-bottom: solid 1px #000000;">Web Site Settings</th>
              </tr>
              <tr>
                <td colspan="2" class="notes">This section will need to be customized for your installation</th>
              </tr>
              <tr>
                <th>Web Path: </th>
                <td><input type="text" name="webpath" value="{$config.Site.path}" size="50"></td>
              </tr>
              <tr>
                <th>Web URL: </th>
                <td><input type="text" name="weburl" value="{$config.Site.url}" size="50"></td>
              </tr>
              <tr>
                <th>Local Path: </th>
                <td><input type="text" name="localpath" value="{$config.Site.local}" size="50"></td>
              </tr>
              <tr>
                <th>Web Title: </th>
                <td><input type="text" name="title" value="{$config.Site.title}" size="50"></td>
              </tr>
              <tr>
                <th>Email Contact: </th>
                <td><input type="text" name="email" value="{$config.Site.email}" size="50"></td>
              </tr>
              <tr>
                <th>Web Language: </th>
                <td><!--<input type="text" name="language" value="{$config.Site.language}" size="50">-->
                <select name="language">
                    <option value="de"{if $config.Site.language == 'de'}selected{/if}>Deutsch</option>  
                    <option value="en"{if $config.Site.language == 'en'}selected{/if}>English</option>  
                    <option value="es"{if $config.Site.language == 'es'}selected{/if}>Español</option>  
                    <option value="fr"{if $config.Site.language == 'fr'}selected{/if}>Français</option> 
                    <option value="ja"{if $config.Site.language == 'ja'}selected{/if}>Japanese</option> 
                    <option value="nl"{if $config.Site.language == 'nl'}selected{/if}>Dutch</option>    
                    <option value="pt-br"{if $config.Site.language == 'pt-br'}selected{/if}>Brazilian Portugese</option>    
                    <option value="zh-cn"{if $config.Site.language == 'zh-cn'}selected{/if}>Simplified Chinese</option> 
                    <option value="zh"{if $config.Site.language == 'zh'}selected{/if}>Chinese</option>  
                </select>
                </td>
              </tr>
              <tr>
                <th>Locale: </th>
                <td><input type="text" name="locale" value="{$config.Site.locale}" size="50"></td>
              </tr>
              <tr>
                <th>Theme: </th>
                <td>
                  <select name="theme">
                  {foreach from=$themeList item=theme}
                    <option value="{$theme}"{if $config.Site.theme == $theme} selected{/if}>{$theme}</option>
                  {/foreach}
                  </select>
                </td>
              </tr>
              <tr>
                <th colspan="2" style="font-weight: bold; border-bottom: solid 1px #000000;">ILS Connection Settings</th>
              </tr>
              <tr>
                <td colspan="2" class="notes">Please set the ILS that VuFind will interact with. Or leave "Sample" for testing purposes.</th>
              </tr>
              <tr>
                <th>ILS: </th>
                <td>
                  <select name="ils">
                    <option value="Sample"{if $config.Catalog.driver == "Sample"} selected{/if}>Sample</option>
                    <option value="Aleph"{if $config.Catalog.driver == "Aleph"} selected{/if}>Aleph</option>
                    <option value="Evergreen"{if $config.Catalog.driver == "Evergreen"} selected{/if}>Evergreen</option>                    
                    <option value="Koha"{if $config.Catalog.driver == "Koha"} selected{/if}>Koha</option>                    
                    <option value="III"{if $config.Catalog.driver == "III"} selected{/if}>Innovative</option>
                    <option value="Unicorn"{if $config.Catalog.driver == "Unicorn"} selected{/if}>Unicorn</option>
                    <option value="Voyager"{if $config.Catalog.driver == "Voyager"} selected{/if}>Voyager</option>
                  </select>
                </td>
              </tr>
              <tr>
                <th colspan="2" style="font-weight: bold; border-bottom: solid 1px #000000;">Local Database Settings</th>
              </tr>
              <tr>
                <td colspan="2" class="notes">This section needs to be changed to match your installation path and database connection information</th>
              </tr>
              <tr>
                <th>Username: </th>
                <td><input type="text" name="dbusername" value="{$dsn.username}" size="50"></td>
              </tr>
              <tr>
                <th>Password: </th>
                <td><input type="text" name="dbpassword" value="{$dsn.password}" size="50"></td>
              </tr>
              <tr>
                <th>Server: </th>
                <td><input type="text" name="dbhost" value="{$dsn.hostspec}" size="50"></td>
              </tr>
              <tr>
                <th>Database Name: </th>
                <td><input type="text" name="dbname" value="{$dsn.database}" size="50"></td>
              </tr>
              <tr>
                <th colspan="2" style="font-weight: bold; border-bottom: solid 1px #000000;">Mail Server Settings</th>
              </tr>
              <tr>
                <td colspan="2" class="notes">This section should not need to be changed</th>
              </tr>
              <tr>
                <th>Mail Server: </th>
                <td><input type="text" name="mailhost" value="{$config.Mail.host}" size="50"></td>
              </tr>
              <tr>
                <th>Mail Port: </th>
                <td><input type="text" name="mailport" value="{$config.Mail.port}" size="50"></td>
              </tr>
              <tr>
                <th colspan="2" style="font-weight: bold; border-bottom: solid 1px #000000;">Book Cover Settings</th>
              </tr>
              <tr>
                <td colspan="2" class="notes">Book Covers are Optional. You can select from Syndetics, Amazon or Google Books</th>
              </tr>
              <tr>
                <th>Provider: </th>
                <td><input type="text" name="bookcover_provider" value="{$config.BookCovers.provider}" size="50"></td>
              </tr>
              <tr>
                <th>Account ID: </th>
                <td><input type="text" name="bokcover_id" value="{$config.BookCovers.id}" size="50"></td>
              </tr>
              <tr>
                <th colspan="2" style="font-weight: bold; border-bottom: solid 1px #000000;">Book Reviews Settings</th>
              </tr>
              <tr>
                <td colspan="2" class="notes">Book Reviews are Optional. You can select from Syndetics or Amazon</th>
              </tr>
              <tr>
                <th>Provider: </th>
                <td><input type="text" name="bookreview_provider" value="{$config.BookReviews.provider}" size="50"></td>
              </tr>
              <tr>
                <th>Account ID: </th>
                <td><input type="text" name="bookreview_id" value="{$config.BookReviews.id}" size="50"></td>
              </tr>
              <tr>
                <th colspan="2" style="font-weight: bold; border-bottom: solid 1px #000000;">LDAP Server Settings</th>
              </tr>
              <tr>
                <td colspan="2" class="notes">LDAP is optional.  With this disabled authentication will take place in the local database</th>
              </tr>
              <tr>
                <th>LDAP Server: </th>
                <td><input type="text" name="ldaphost" value="{$config.LDAP.host}" size="50"></td>
              </tr>
              <tr>
                <th>LDAP Port: </th>
                <td><input type="text" name="ldapport" value="{$config.LDAP.port}" size="50"></td>
              </tr>
              <tr>
                <th>LDAP Base DN: </th>
                <td><input type="text" name="ldapbasedn" value="{$config.LDAP.basedn}" size="50"></td>
              </tr>
              <tr>
                <th>LDAP UID: </th>
                <td><input type="text" name="ldapuid" value="{$config.LDAP.uid}" size="50"></td>
              </tr>
              <tr>
                <th colspan="2" style="font-weight: bold; border-bottom: solid 1px #000000;">COinS Settings</th>
              </tr>
              <tr>
                <td colspan="2" class="notes">This section can be changed to create a COinS identifier</th>
              </tr>
              <tr>
                <th>Identifier: </th>
                <td><input type="text" name="coinsID" value="{$config.COinS.identifier}" size="50"></td>
              </tr>
              <tr>
                <th colspan="2" style="font-weight: bold; border-bottom: solid 1px #000000;">OAI Server Settings</th>
              </tr>
              <tr>
                <td colspan="2" class="notes">This section can be changed to create an OAI identifier</th>
              </tr>
              <tr>
                <th>Identifier: </th>
                <td><input type="text" name="oaiID" value="{$config.OAI.identifier}" size="50"></td>
              </tr>
              <tr>
                <th colspan="2" style="font-weight: bold; border-bottom: solid 1px #000000;">OpenURL Link Resolver Settings</th>
              </tr>
              <tr>
                <td colspan="2" class="notes">OpenURL Link Resolver is Optional.</th>
              </tr>
              <tr>
                <th>Link Resolver URL: </th>
                <td><input type="text" name="openurl" value="{$config.OpenURL.url}" size="50"></td>
              </tr>
        <tr>
                <th colspan="2" style="font-weight: bold; border-bottom: solid 1px #000000;">EZProxy Settings</th>
              </tr>
              <tr>
                <td colspan="2" class="notes">EZProxy is Optional.</th>
              </tr>
        <tr>
                <th>EZProxy Host: </th>
                <td><input type="text" name="ezproxyhost" value="{$config.EZproxy.host}" size="50"></td>
              </tr>
            </table>
            <input type="submit" name="submit" value="Save">
          </form>
        </div>
      </div>

    </div>
  </div>
</div>