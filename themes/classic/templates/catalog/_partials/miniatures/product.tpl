{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}
{block name='product_miniature_item'}
{literal}
<style>
  .product-miniature .product-flag { background-color: #ee7a03 !important; }
  .product-miniature .product-flag.on-sale { display: none !important; }
  .product-miniature .wishlist-button-add, .product-miniature .wishlist-button-product { display: none !important; }

  /* === Animation miniatures === */
  .product-miniature {
    position: relative;
    opacity: 0;
    transform: translateY(24px);
    transition: opacity 0.6s ease, transform 0.6s ease, box-shadow 0.35s ease;
    will-change: opacity, transform;
  }
  .product-miniature.is-visible {
    opacity: 1;
    transform: translateY(0);
  }
  .product-miniature .thumbnail-container {
    transition: box-shadow 0.35s ease;
  }
  .product-miniature .thumbnail-top {
    position: relative;
    overflow: hidden;
  }
  .product-miniature .thumbnail-top .product-thumbnail img {
    transition: transform 0.5s ease;
    display: block;
    width: 100%;
    height: auto;
  }
  @media (hover: hover) {
    .product-miniature:hover .thumbnail-container {
      box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12);
    }
    .product-miniature:hover .thumbnail-top .product-thumbnail img {
      transform: scale(1.06);
    }
  }

  /* Mobile : pulse léger quand la miniature entre dans le viewport */
  @media (hover: none) {
    .product-miniature.is-visible .thumbnail-top .product-thumbnail img {
      animation: ci-mini-pulse 0.9s ease-out 0.15s 1;
    }
    @keyframes ci-mini-pulse {
      0%   { transform: scale(1); }
      55%  { transform: scale(1.04); }
      100% { transform: scale(1); }
    }
  }

  @media (prefers-reduced-motion: reduce) {
    .product-miniature {
      opacity: 1;
      transform: none;
      transition: none;
    }
    .product-miniature .thumbnail-top .product-thumbnail img,
    .product-miniature .thumbnail-container {
      transition: none;
      animation: none;
    }
  }

  /* Titre produit forcé sur 1 ligne avec ellipsis dans toutes les vignettes */
  .product-miniature .product-title {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 6px;
  }
  .product-miniature .product-title a {
    display: inline-block;
    max-width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    vertical-align: bottom;
  }
</style>
{/literal}
<div class="js-product product{if !empty($productClasses)} {$productClasses}{/if}">
  <article class="product-miniature js-product-miniature" data-id-product="{$product.id_product}" data-id-product-attribute="{$product.id_product_attribute}">
    <div class="thumbnail-container">
      <div class="thumbnail-top">
        {block name='product_thumbnail'}
          {if $product.cover}
            <a href="{$product.url}" class="thumbnail product-thumbnail">
              <picture>
                {if !empty($product.cover.bySize.home_default.sources.avif)}<source srcset="{$product.cover.bySize.home_default.sources.avif}" type="image/avif">{/if}
                {if !empty($product.cover.bySize.home_default.sources.webp)}<source srcset="{$product.cover.bySize.home_default.sources.webp}" type="image/webp">{/if}
                <img
                  src="{$product.cover.bySize.home_default.url}"
                  alt="{if !empty($product.cover.legend)}{$product.cover.legend}{else}{$product.name|truncate:37:'...'}{/if}"
                  loading="lazy"
                  data-full-size-image-url="{$product.cover.large.url}"
                  width="{$product.cover.bySize.home_default.width}"
                  height="{$product.cover.bySize.home_default.height}"
                />
              </picture>
            </a>
          {else}
            <a href="{$product.url}" class="thumbnail product-thumbnail">
              <picture>
                {if !empty($urls.no_picture_image.bySize.home_default.sources.avif)}<source srcset="{$urls.no_picture_image.bySize.home_default.sources.avif}" type="image/avif">{/if}
                {if !empty($urls.no_picture_image.bySize.home_default.sources.webp)}<source srcset="{$urls.no_picture_image.bySize.home_default.sources.webp}" type="image/webp">{/if}
                <img
                  src="{$urls.no_picture_image.bySize.home_default.url}"
                  loading="lazy"
                  width="{$urls.no_picture_image.bySize.home_default.width}"
                  height="{$urls.no_picture_image.bySize.home_default.height}"
                />
              </picture>
            </a>
          {/if}
        {/block}

        <div class="highlighted-informations{if !$product.main_variants} no-variants{/if}">
          {block name='quick_view'}{/block}

          {block name='product_variants'}
            {if $product.main_variants}
              {include file='catalog/_partials/variant-links.tpl' variants=$product.main_variants}
            {/if}
          {/block}
        </div>
      </div>

      <div class="product-description">
        {block name='product_name'}
          {if $page.page_name == 'index'}
            <h3 class="h3 product-title"><a href="{$product.url}" content="{$product.url}">{$product.name|truncate:37:'...'}</a></h3>
          {else}
            <h2 class="h3 product-title"><a href="{$product.url}" content="{$product.url}">{$product.name|truncate:37:'...'}</a></h2>
          {/if}
        {/block}

        {block name='product_price_and_shipping'}
          {if $product.show_price}
            <div class="product-price-and-shipping">
              {if $product.has_discount}
                {hook h='displayProductPriceBlock' product=$product type="old_price"}

                <span class="regular-price" aria-label="{l s='Regular price' d='Shop.Theme.Catalog'}">{$product.regular_price}</span>
                {if $product.discount_type === 'percentage'}
                  <span class="discount-percentage discount-product">{$product.discount_percentage}</span>
                {elseif $product.discount_type === 'amount'}
                  <span class="discount-amount discount-product">{$product.discount_amount_to_display}</span>
                {/if}
              {/if}

              {hook h='displayProductPriceBlock' product=$product type="before_price"}

              <span class="price" aria-label="{l s='Price' d='Shop.Theme.Catalog'}">
                {capture name='custom_price'}{hook h='displayProductPriceBlock' product=$product type='custom_price' hook_origin='products_list'}{/capture}
                {if '' !== $smarty.capture.custom_price}
                  {$smarty.capture.custom_price nofilter}
                {else}
                  {$product.price}
                {/if}
              </span>

              {hook h='displayProductPriceBlock' product=$product type='unit_price'}

              {hook h='displayProductPriceBlock' product=$product type='weight'}
            </div>
          {/if}
        {/block}

        {block name='product_reviews'}
          {hook h='displayProductListReviews' product=$product}
        {/block}
      </div>

      {include file='catalog/_partials/product-flags.tpl'}
    </div>
  </article>
</div>
{literal}
<script>
(function () {
  if (window.__ciMiniatureInit) return;
  window.__ciMiniatureInit = true;

  function reveal(el) { el.classList.add('is-visible'); }

  function init() {
    var nodes = document.querySelectorAll('.product-miniature');
    if (!nodes.length) return;

    if (!('IntersectionObserver' in window)) {
      nodes.forEach(reveal);
      return;
    }

    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          reveal(entry.target);
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

    nodes.forEach(function (el) {
      if (!el.classList.contains('is-visible')) io.observe(el);
    });

    // Capture les miniatures injectees apres coup (pagination ajax, sliders)
    if ('MutationObserver' in window) {
      var mo = new MutationObserver(function (muts) {
        muts.forEach(function (m) {
          m.addedNodes && m.addedNodes.forEach(function (n) {
            if (n.nodeType !== 1) return;
            if (n.classList && n.classList.contains('product-miniature')) {
              io.observe(n);
            } else if (n.querySelectorAll) {
              n.querySelectorAll('.product-miniature:not(.is-visible)').forEach(function (sub) {
                io.observe(sub);
              });
            }
          });
        });
      });
      mo.observe(document.body, { childList: true, subtree: true });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
</script>
{/literal}
{/block}
