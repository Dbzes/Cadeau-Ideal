<div class="col-12">
  <ul class="cms-footer-links">
    {foreach from=$cms_footer_links item=link name=cms_links}
      <li>
        <a href="{$link.url}">{$link.title}</a>
      </li>
      {if !$smarty.foreach.cms_links.last}
        <li class="cms-footer-sep" aria-hidden="true"> - </li>
      {/if}
    {/foreach}
  </ul>
</div>
