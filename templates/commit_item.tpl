{*
 *  commit_item.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Commit view item template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
 <tr class="{$class}">
 
 {if $status == "A"}
  <td><a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$to_id}&hb={$hash}&f={$file}" class="list">{$file}</a></td>
  <td><span class="newfile">[new {$to_filetype}{if $isreg} with mode: {$to_mode_cut}{/if}]</span></td>
  <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$to_id}&hb={$hash}&f={$file}">blob</a></td>
 {elseif $status == "D"}
  <td><a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$from_id}&hb={$hash}&f={$file}" class="list">{$file}</a></td>
  <td><span class="deletedfile">[deleted {$from_filetype}]</span></td>
  <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$from_id}&hb={$hash}&f={$file}">blob</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=history&h={$hash}&f={$file}">history</a></td>
 {elseif $status == "M" || $status == "T"}
  <td>
  {if $to_id != $from_id}
    <a href="{$SCRIPT_NAME}?p={$project}&a=blobdiff&h={$to_id}&hp={$from_id}&hb={$hash}&f={$file}" class="list">{$file}</a>
  {else}
    <a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$to_id}&hb={$hash}&f={$file}" class="list">{$file}</a>
  {/if}
  </td>
  <td>{if $from_mode != $to_mode} <span class="changedfile">[changed{$modechange}]</span>{/if}</td>
  <td class="link">
  <a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$to_id}&hb={$hash}&f={$file}">blob</a>{if $to_id != $from_id} | <a href="{$SCRIPT_NAME}?p={$project}&a=blobdiff&h={$to_id}&hp={$from_id}&hb={$hash}&f={$file}">diff</a>{/if} | <a href="{$SCRIPT_NAME}?p={$project}&a=history&h={$hash}&f={$file}">history</a></td>
 {elseif $status == "R"}
  <td><a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$to_id}&hb={$hash}&f={$to_file}" class="list">{$to_file}</a></td>
  <td><span class="movedfile">[moved from <a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$from_id}&hb={$hash}&f={$from_file}" class="list">{$from_file}</a> with {$similarity}% similarity{$simmodechg}]</span></td>
  <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$to_id}&hb={$hash}&f={$to_file}">blob</a>{if $to_id != $from_id} | <a href="{$SCRIPT_NAME}?p={$project}&a=blobdiff&h={$to_id}&hp={$from_id}&hb={$hash}&f={$to_file}">diff</a>{/if}</td>
 {/if}

 </tr>
