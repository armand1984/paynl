{**
 * LANDING PAGES
 *
 * PHP version 5
 *
 * @category  Prestashop module
 * @package   landingpages
 * @author    Brandweb <office@brandweb.ro>
 * @copyright 2015 Brandweb
 * @license   GNU General Public License version 2
 * @version   1.0
 *}

<link rel="stylesheet" href="/modules/paynl_paymentmethods/css/paynl.css" />
<link rel="stylesheet" href="https://www.pay.nl/style/payment_profiles/75x75/sprite.css" />


{foreach from=$profiles key=k item=v}
    <div class="row">
        <div class="col-xs-12 col-md-6">
            <p class="payment_module" >
                <a data-ajax="false" class="paynl_paymentmethod " href="{$link->getModuleLink('paynl_paymentmethods', 'payment', [pid => {$v.id}], true)|escape:'html'}" title="{l s=$v.name mod='paynl_paymentmethods'}">
                    <label class='pp_s75 pp{$v.id}'></label>
                    {*<img src="https://admin.pay.nl/images/payment_profiles/{$v.id}.gif" alt="{$v.name}" width="86" height="49" />*}
                    {$v.name}{if $v.extraCosts != 0}  <span class="">+ &euro; {number_format($v.extraCosts,2,',', '.')}</span> {/if}
                </a>
            </p>
        </div>
    </div>
{/foreach}