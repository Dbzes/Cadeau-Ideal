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
{extends 'customer/page.tpl'}

{block name='page_title'}
  {l s='Your personal information' d='Shop.Theme.Customeraccount'}
{/block}

{block name='page_content'}
  <style>
    /* Masquer la ligne Titre (civilité) */
    body#identity .form-group:has([name="id_gender"]),
    body#identity label[for="field-id_gender"] {
      display: none !important;
    }
    /* Bouton Enregistrer en bleu du site */
    body#identity .btn-primary {
      background-color: #004774 !important;
      border-color: #004774 !important;
      border-radius: 0 !important;
    }
    body#identity .btn-primary:hover {
      background-color: #003359 !important;
      border-color: #003359 !important;
    }
    /* Boutons Afficher (toggle password) en orange du site */
    body#identity .btn[data-action="show-password"],
    body#identity .btn-show-password,
    body#identity .field-password-policy .btn {
      background-color: #ee7a03 !important;
      border-color: #ee7a03 !important;
      color: #fff !important;
      border-radius: 0 !important;
    }
    /* Inputs en border orange, pas de radius */
    body#identity .form-control,
    body#identity input[type="text"],
    body#identity input[type="email"],
    body#identity input[type="password"],
    body#identity select {
      border: 1px solid #ee7a03 !important;
      border-radius: 0 !important;
    }
  </style>
  {render file='customer/_partials/customer-form.tpl' ui=$customer_form}
{/block}
