<div class="encartshp-container">
  <div class="encartshp-grid">
    {foreach from=$encarts item=encart}
      <div class="encartshp-item encartshp-item--{$encart.position}">
        {if $encart.link}
          <a href="{$encart.link|escape:'htmlall':'UTF-8'}"
             {if $encart.title}title="{$encart.title|escape:'htmlall':'UTF-8'}"{/if}
             {if $encart.new_tab}target="_blank" rel="noopener noreferrer"{/if}>
        {/if}

        {if $encart.has_image}
          <img src="{$encart.image_url|escape:'htmlall':'UTF-8'}"
               alt="{$encart.alt|escape:'htmlall':'UTF-8'}"
               class="encartshp-img"
               width="545"
               height="340"
               loading="{if $encart.position <= 2}eager{else}lazy{/if}" />
        {else}
          <div class="encartshp-placeholder"></div>
        {/if}

        {if $encart.link}
          </a>
        {/if}
      </div>
    {/foreach}
  </div>
</div>
