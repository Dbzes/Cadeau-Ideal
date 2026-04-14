{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
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
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 *}
{foreach $list as $product}
<tr>
	<td align="center" style="border:1px solid #D6D4D4; padding: 5px;">
		{if !empty($product['image_url'])}
			<img src="{$product['image_url']}" alt="{$product['name']|escape:'htmlall'}" width="70" style="max-width:70px; height:auto; display:inline-block;">
		{/if}
	</td>
	<td style="border:1px solid #D6D4D4;">
		<table class="table">
			<tr>
				<td width="5">&nbsp;</td>
				<td>
					<font size="2" face="Open-sans, sans-serif" color="#555454">
						<strong>{$product['name']}</strong><br>
						{$product['reference']}
					</font>
				</td>
				<td width="5">&nbsp;</td>
			</tr>
		</table>
	</td>
	<td style="border:1px solid #D6D4D4;">
		<table class="table">
			<tr>
				<td width="5">&nbsp;</td>
				<td align="right">
					<font size="2" face="Open-sans, sans-serif" color="#555454">
						{$product['unit_price']}
					</font>
				</td>
				<td width="5">&nbsp;</td>
			</tr>
		</table>
	</td>
	<td style="border:1px solid #D6D4D4;">
		<table class="table">
			<tr>
				<td width="5">&nbsp;</td>
				<td align="right">
					<font size="2" face="Open-sans, sans-serif" color="#555454">
						{$product['quantity']}
					</font>
				</td>
				<td width="5">&nbsp;</td>
			</tr>
		</table>
	</td>
	<td style="border:1px solid #D6D4D4;">
		<table class="table">
			<tr>
				<td width="5">&nbsp;</td>
				<td align="right">
					<font size="2" face="Open-sans, sans-serif" color="#555454">
						{$product['price']}
					</font>
				</td>
				<td width="5">&nbsp;</td>
			</tr>
		</table>
	</td>
</tr>
  {if count($product['customization']) > 1}
  	{foreach $product['customization'] as $customization}
  		<tr>
  		<td colspan="3" style="border:1px solid #D6D4D4;">
  			<table class="table">
  				<tr>
  					<td width="5">&nbsp;</td>
  					<td>
  						<font size="2" face="Open-sans, sans-serif" color="#555454">
  							{$customization['customization_text']}
  						</font>
  					</td>
  					<td width="5">&nbsp;</td>
  				</tr>
  			</table>
  		</td>
  		<td style="border:1px solid #D6D4D4;">
  			<table class="table">
  				<tr>
  					<td width="5">&nbsp;</td>
  					<td align="right">
  						<font size="2" face="Open-sans, sans-serif" color="#555454">
  							{if count($product['customization']) > 1}
  								{$customization['customization_quantity']}
  							{/if}
  						</font>
  					</td>
  					<td width="5">&nbsp;</td>
  				</tr>
  			</table>
  		</td>
  		<td style="border:1px solid #D6D4D4;"></td>
  	</tr>
  	{/foreach}
  {/if}
{/foreach}
