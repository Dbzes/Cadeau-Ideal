<div class="encartshp-container">
  <div class="encartshp-grid-grands">
    {foreach from=$grands item=encart}
      <div class="encartshp-item encartshp-grand">
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
               loading="eager" />
        {else}
          <div class="encartshp-placeholder encartshp-placeholder--grand"></div>
        {/if}

        {if $encart.link}
          </a>
        {/if}
      </div>
    {/foreach}
  </div>
  {if $grands2|count > 0}
  <div class="encartshp-grid-grands">
    {foreach from=$grands2 item=encart}
      <div class="encartshp-item encartshp-grand">
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
               loading="lazy" />
        {else}
          <div class="encartshp-placeholder encartshp-placeholder--grand"></div>
        {/if}

        {if $encart.link}
          </a>
        {/if}
      </div>
    {/foreach}
  </div>
  {/if}
  <div class="encartshp-grid-petits">
    {foreach from=$petits item=encart}
      <div class="encartshp-item encartshp-petit">
        {if $encart.link}
          <a href="{$encart.link|escape:'htmlall':'UTF-8'}"
             {if $encart.title}title="{$encart.title|escape:'htmlall':'UTF-8'}"{/if}
             {if $encart.new_tab}target="_blank" rel="noopener noreferrer"{/if}>
        {/if}

        {if $encart.has_image}
          <img src="{$encart.image_url|escape:'htmlall':'UTF-8'}"
               alt="{$encart.alt|escape:'htmlall':'UTF-8'}"
               class="encartshp-img"
               width="300"
               height="183"
               loading="lazy" />
        {else}
          <div class="encartshp-placeholder encartshp-placeholder--petit"></div>
        {/if}

        {if $encart.link}
          </a>
        {/if}
      </div>
    {/foreach}
  </div>
</div>
