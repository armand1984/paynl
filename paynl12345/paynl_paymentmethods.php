<?php
/**
 * PAYNL PAYMENT METHODS
 *
 * PHP version 5
 *
 * @category  Prestashop module
 * @package   paynl_paymentmethods
 * @author    Novisites <info@novisites.nl>
 * @copyright 2015 Novisites
 * @license   GNU General Public License version 2
 * @version   3.2.2
 */

if (!defined('_PS_VERSION_'))
exit;

require_once _PS_MODULE_DIR_.'paynl_paymentmethods/includes/classes/Autoload.php';

class PaynlPaymentmethods extends PaymentModule {

public function __construct()
{
$this->name = 'paynl_paymentmethods';
$this->tab = 'payments_gateways';
$this->version = '3.2.2';
$this->post_errors = array();
$this->author = 'Novisites';
$this->currencies = true;
$this->currencies_mode = 'radio';

parent::__construct();

$this->page = basename(__FILE__, '.php');
$this->displayName = $this->l('Pay.nl Payment methods');
$this->description = $this->l('Accept payments by Pay.nl');
$this->confirmUninstall = $this->l('Are you sure you want to delete your details?');

if (_PS_VERSION_ < '1.5')
require(_PS_MODULE_DIR_.$this->name.'/backward_compatibility/backward.php');
}

public function validateOrderPay($id_cart, $id_order_state, $amount_paid, $extra_costs, $payment_method = 'Unknown',
$message = null, $extra_vars = array(), $currency_special = null, $dont_touch_amount = false, $secure_key = false, Shop $shop = null)
{
$status_pending = Configuration::get('PAYNL_WAIT');
$status_paid = Configuration::get('PAYNL_SUCCESS');

// Als er nog geen order van dit cartid is, de order valideren.
$order_id = Order::getOrderByCartId($id_cart);
if ($order_id == false)
{
if ($id_order_state == $status_paid)
{
if ($extra_costs != 0)

$id_order_state_tmp = $status_pending;
else
{
$id_order_state_tmp = $status_paid;

}
}
else

$id_order_state_tmp = $id_order_state;

$result = parent::validateOrder($id_cart, $id_order_state_tmp, $amount_paid, $this->displayName, $message, $extra_vars,
$currency_special, $dont_touch_amount, $secure_key, $shop);
$order_id = $this->current_order;

if ($extra_costs == 0 && $id_order_state_tmp == $status_paid)
{
//Als er geen extra kosten zijn, en de order staat op betaald zijn we klaar
return $result;
}
}

if ($order_id && $id_order_state == $status_paid)
{
$order = new Order($order_id);
$shipping_cost = $order->total_shipping;

$new_shipping_costs = $shipping_cost + $extra_costs;
$extra_costs_excl = round($extra_costs / (1 + (21 / 100)), 2);

if ($extra_costs != 0)
{
//als de order extra kosten heeft, moeten deze worden toegevoegd.
$order->total_shipping = $new_shipping_costs;
$order->total_shipping_tax_excl = $order->total_shipping_tax_excl + $extra_costs_excl;
$order->total_shipping_tax_incl = $new_shipping_costs;

$order->total_paid_tax_excl = $order->total_paid_tax_excl + $extra_costs_excl;

$order->total_paid_tax_incl = $order->total_paid_real = $order->total_paid = $order->total_paid + $extra_costs;
}

$result = $order->addOrderPayment($amount_paid, $payment_method, $extra_vars['transaction_id']);

if (number_format($order->total_paid_tax_incl, 2) !== number_format($amount_paid, 2))

$id_order_state = Configuration::get('PS_OS_ERROR');

//paymentid ophalen
$order_payment = OrderPayment::getByOrderId($order->id);

$history = new OrderHistory();
$history->id_order = (int)$order->id;
$history->changeIdOrderState((int)$id_order_state, $order, $order_payment);
$res = Db::getInstance()->getRow('
			SELECT `invoice_number`, `invoice_date`, `delivery_number`, `delivery_date`
FROM `'._DB_PREFIX_.'orders`
WHERE `id_order` = '.(int)$order->id);
$order->invoice_date = $res['invoice_date'];
$order->invoice_number = $res['invoice_number'];
$order->delivery_date = $res['delivery_date'];
$order->delivery_number = $res['delivery_number'];

$order->update();

$history->addWithemail();
}
return $result;
}

public function install()
{
if (!parent::install() || !$this->createTransactionTable() || !Configuration::updateValue('PAYNL_TOKEN', '')
|| !Configuration::updateValue('PAYNL_SERVICE_ID', '')
|| !Configuration::updateValue('PAYNL_ORDER_DESC', '') || !Configuration::updateValue('PAYNL_WAIT', '10')
|| !Configuration::updateValue('PAYNL_SUCCESS', '2') || !Configuration::updateValue('PAYNL_AMOUNTNOTVALID', '0')
|| !Configuration::updateValue('PAYNL_CANCEL', '6') || !Configuration::updateValue('PAYNL_COUNTRY_EXCEPTIONS', '')
|| !Configuration::updateValue('PAYNL_PAYMENT_METHOD_ORDER', '') || !$this->registerHook('paymentReturn') || !$this->registerHook('payment'))

return false;

return true;
}

private function createTransactionTable()
{
$sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'pay_transactions` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`transaction_id` varchar(50) NOT NULL,
`option_id` int(11) NOT NULL,
`amount` int(11) NOT NULL,
`currency` char(3) NOT NULL,
`order_id` int(11) NOT NULL,
`status` varchar(10) NOT NULL DEFAULT "PENDING",
`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
`last_update` datetime DEFAULT NULL,
`start_data` text NOT NULL,
PRIMARY KEY (`id`)
  ) ENGINE=myisam AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;';

DB::getInstance()->execute($sql);
return true;
}

public function validateOnStart($payment_method_id)
{
$arr_validate_on_start = Configuration::get('PAYNL_VALIDATE_ON_START');
if (!empty($arr_validate_on_start))
{
$arr_validate_on_start = unserialize($arr_validate_on_start);
if (Tools::getIsset($arr_validate_on_start[$payment_method_id]) && $arr_validate_on_start[$payment_method_id] == 1)
{

return true;
}
}

return false;
}

public function getExtraCosts($payment_method_id, $total_amount)
{
$arr_extra_costs = Configuration::get('PAYNL_PAYMENT_EXTRA_COSTS');
$arr_extra_costs = unserialize($arr_extra_costs);

$arr_extra_costs = $arr_extra_costs[$payment_method_id];
if (empty($arr_extra_costs))

return 0;

$fixed = !empty($arr_extra_costs['fixed']) ? $arr_extra_costs['fixed'] : 0;
$percentage = !empty($arr_extra_costs['percentage']) ? $arr_extra_costs['percentage'] : 0;
$max = !empty($arr_extra_costs['max']) ? $arr_extra_costs['max'] : 0;

$extra_costs = $fixed;
$extra_costs += ($total_amount * ($percentage / 100));
if ($extra_costs > $max && $max != 0)

$extra_costs = $max;

return round($extra_costs, 2);
}

public function getPaymentMethodName($payment_method_id)
{
$token = Configuration::get('PAYNL_TOKEN');
$service_id = Configuration::get('PAYNL_SERVICE_ID');

$api_service = new PayApiGetservice();
$api_service->setApiToken($token);
$api_service->setServiceId($service_id);

$result = $api_service->doRequest();

if (Tools::getIsset($result['paymentOptions'][$payment_method_id]))

return $result['paymentOptions'][$payment_method_id]['name'];
else
return false;

}

public function uninstall()
{
if (!Configuration::deleteByName('PAYNL_TOKEN', '') || !Configuration::deleteByName('PAYNL_SERVICE_ID', '')
|| !Configuration::deleteByName('PAYNL_ORDER_DESC', '')
|| !Configuration::deleteByName('PAYNL_WAIT', '0') || !Configuration::deleteByName('PAYNL_SUCCESS', '0')
|| !Configuration::deleteByName('PAYNL_AMOUNTNOTVALID', '0') || !Configuration::deleteByName('PAYNL_CANCEL', '0')
|| !Configuration::deleteByName('PAYNL_COUNTRY_EXCEPTIONS', '0') || !Configuration::deleteByName('PAYNL_PAYMENT_METHOD_ORDER', '')
|| !parent::uninstall())

return false;

return true;
}

public function hookPayment($params)
{
$obj_currency = $this->getCurrency();
$int_order_amount = round(number_format(Tools::convertPrice($params['cart']->getOrderTotal(), $obj_currency), 2, '.', '') * 100);

if ($this->validateOrderData())
{

$token = Configuration::get('PAYNL_TOKEN');
$service_id = Configuration::get('PAYNL_SERVICE_ID');

$method_order = Configuration::get('PAYNL_PAYMENT_METHOD_ORDER');
$method_order = unserialize($method_order);
if ($method_order == false)

$method_order = array();

$country_exceptions = Configuration::get('PAYNL_COUNTRY_EXCEPTIONS');
$country_exceptions = unserialize($country_exceptions);
if ($country_exceptions == false)

$country_exceptions = array();

$api_getservice = new PayApiGetservice();
$api_getservice->setApiToken($token);
$api_getservice->setServiceId($service_id);

$active_profiles = $api_getservice->doRequest();
$active_profiles = $active_profiles['paymentOptions'];

$paymentaddress = new Address($params['cart']->id_address_invoice);
$countryid = $paymentaddress->id_country;

// Only the profiles of the target country should remain in this array :). (Only when the count is > 0, otherwise it might indicate problems)
if (count($country_exceptions) > 0)
{
if (Tools::getIsset($country_exceptions[$countryid]))
{
foreach ($active_profiles as $id => $profile)
{
if (!Tools::getIsset($country_exceptions[$countryid][$profile['id']]))

unset($active_profiles[$id]);

}
}
}

// Order remaining profiles based by order...
asort($method_order);

$active_profiles_temp = $active_profiles;
$active_profiles = array();

foreach (array_keys($method_order) as $i_profile_id)
{
foreach ($active_profiles_temp as $i_key => $arr_active_profile)
{
if ($arr_active_profile['id'] == $i_profile_id)
{
$arr_active_profile['extraCosts'] = number_format($this->getExtraCosts($arr_active_profile['id'], $int_order_amount / 100), 2);
array_push($active_profiles, $arr_active_profile);
unset($active_profiles_temp[$i_key]);
}
}
}
//var_dump($active_profiles);

$this->context->smarty->assign(array(
'this_path' => $this->_path,
'profiles' => $active_profiles,
//'banks' => $paynl->getIdealBanks(),
'this_path_ssl' => (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8')
.__PS_BASE_URI__.'modules/'.$this->name.'/'
));

return $this->display(_PS_MODULE_DIR_.'/'.$this->name.'/'.$this->name.'.php', 'views/templates/hook/payment.tpl');
}
else

return;

}

public function hookPaymentReturn()
{
if (!$this->active)
return;
//$customer = new Customer($params['objOrder']->id_customer);
//$this->context->smarty->assign(array(
//'reference_order' => $params['objOrder']->id,
//'email' => $customer->email,
//'id_order_formatted' => $params['objOrder']->reference,
//'is_guest' => $customer->is_guest,
//));
//
//return $this->display(__FILE__, 'return_paid.tpl');
}

public function getContent()
{
$this->_html = '<h2>'.$this->displayName.'</h2>';

if (Tools::getIsset(Tools::GETVALUE('submitPaynl')))
{
if (!Tools::getIsset(Tools::GETVALUE('api')))
Tools::setValue('api', 1);

if (!count($this->post_errors))
{
Configuration::updateValue('PAYNL_TOKEN', Tools::GETVALUE('paynltoken'));
Configuration::updateValue('PAYNL_SERVICE_ID', Tools::GETVALUE('service_id'));
Configuration::updateValue('PAYNL_WAIT', Tools::GETVALUE('wait'));
Configuration::updateValue('PAYNL_SUCCESS', Tools::GETVALUE('success'));
Configuration::updateValue('PAYNL_CANCEL', Tools::GETVALUE('cancel'));
if (Tools::getIsset(Tools::GETVALUE('enaC')))

Configuration::updateValue('PAYNL_COUNTRY_EXCEPTIONS', serialize(Tools::GETVALUE('enaC')));

if (Tools::getIsset(Tools::GETVALUE('enaO')))

Configuration::updateValue('PAYNL_PAYMENT_METHOD_ORDER', serialize(Tools::GETVALUE('enaO')));

if (Tools::getIsset(Tools::GETVALUE('payExtraCosts')))
{
//kommas voor punten vervangen, en zorgen dat het allemaal getallen zijn
$arr_extra_costs = array();

foreach (Tools::GETVALUE('payExtraCosts') as $payment_method_id => $payment_method)
{
foreach ($payment_method as $type => $value)
{
$value = str_replace(',', '.', $value);
$value = $value * 1;
if ($value == 0)
$value = '';
$arr_extra_costs[$payment_method_id][$type] = $value;
}
}
Configuration::updateValue('PAYNL_PAYMENT_EXTRA_COSTS', serialize($arr_extra_costs));
}
if (Tools::getIsset(Tools::GETVALUE('validateOnStart')))

Configuration::updateValue('PAYNL_VALIDATE_ON_START', serialize(Tools::GETVALUE('validateOnStart')));

$this->displayConf();
}
else

$this->displayErrors();

}

$this->displayPaynl();
$this->displayFormSettings();

return $this->_html;
}

public function displayConf()
{
$this->_html .= '
<div class="conf confirm">
  <img src="../img/admin/ok.gif" alt="'.$this->l('Confirmation').'" />
  '.$this->l('Settings updated').'
</div>';
}

public function displayErrors()
{
$nb_errors = count($this->post_errors);
$this->_html .= '
<div class="alert error">
  <h3>'.($nb_errors > 1 ? $this->l('There are') : $this->l('There is')).' '.$nb_errors.' '
.($nb_errors > 1 ? $this->l('errors') : $this->l('error')).'</h3>
  <ol>';
foreach ($this->post_errors as $error)
$this->_html .= '<li>'.$error.'</li>';
$this->_html .= '
  </ol>
</div>';
}

public function displayPaynl()
{
$this->_html .= '
<img src="../modules/paynl_paymentmethods/pay.nl.logo.gif" height="20%" width="20%" style="float:left; margin-right:15px;" />
<b>'.$this->l('This module allows you to accept payments by Pay.nl.').'</b>
<br /><br /><br />';
}

public function displayFormSettings()
{
$arr_config = array();
$arr_config[] = 'PAYNL_TOKEN';
$arr_config[] = 'PAYNL_SERVICE_ID';
$arr_config[] = 'PAYNL_WAIT';
$arr_config[] = 'PAYNL_SUCCESS';
$arr_config[] = 'PAYNL_AMOUNTNOTVALID';
$arr_config[] = 'PAYNL_CANCEL';
$arr_config[] = 'PAYNL_COUNTRY_EXCEPTIONS';
$arr_config[] = 'PAYNL_PAYMENT_METHOD_ORDER';
$arr_config[] = 'PAYNL_PAYMENT_EXTRA_COSTS';
$arr_config[] = 'PAYNL_VALIDATE_ON_START';

$conf = Configuration::getMultiple($arr_config);

$paynltoken = array_key_exists('paynltoken', Tools::GETVALUE) ? Tools::GETVALUE('paynltoken')
: (array_key_exists('PAYNL_TOKEN', $conf) ? $conf['PAYNL_TOKEN'] : '');
$service_id = array_key_exists('service_id', Tools::GETVALUE) ? Tools::GETVALUE('service_id')
: (array_key_exists('PAYNL_SERVICE_ID', $conf) ? $conf['PAYNL_SERVICE_ID'] : '');

$wait = array_key_exists('wait', Tools::GETVALUE) ? Tools::GETVALUE('wait')
: (array_key_exists('PAYNL_WAIT', $conf) ? $conf['PAYNL_WAIT'] : '10');
$success = array_key_exists('success', Tools::GETVALUE) ? Tools::GETVALUE('success')
: (array_key_exists('PAYNL_SUCCESS', $conf) ? $conf['PAYNL_SUCCESS'] : '2');
//$amountnotvalid = array_key_exists('amountnotvalid', Tools::GETVALUE) ? Tools::GETVALUE('amountnotvalid')
//: (array_key_exists('PAYNL_AMOUNTNOTVALID', $conf) ? $conf['PAYNL_AMOUNTNOTVALID'] : '1');
$cancel = array_key_exists('cancel', Tools::GETVALUE) ? Tools::GETVALUE('cancel')
: (array_key_exists('PAYNL_CANCEL', $conf) ? $conf['PAYNL_CANCEL'] : '6');

// Get states
$states = OrderState::getOrderStates((int)$this->context->cookie->id_lang);

$os_wait = '<select name="wait">';
foreach ($states as $state)
{
if ($state['logable'] == 0)
{
$selected = ($state['id_order_state'] == $wait) ? ' selected' : '';
$os_wait .= '<option value="'.$state['id_order_state'].'"'.$selected.'>'.$state['name'].'</option>';
}
}
$os_wait .= '</select>';
$os_success = '<select name="success">';
foreach ($states as $state)
{
if ($state['logable'] == 1)
{
$selected = ($state['id_order_state'] == $success) ? ' selected' : '';
$os_success .= '<option value="'.$state['id_order_state'].'"'.$selected.'>'.$state['name'].'</option>';
}
}
$os_success .= '</select>';

$os_cancel = '<select name="cancel">';
foreach ($states as $state)
{
if ($state['logable'] == 0)
{
$selected = ($state['id_order_state'] == $cancel) ? ' selected' : '';
$os_cancel .= '<option value="'.$state['id_order_state'].'"'.$selected.'>'.$state['name'].'</option>';
}
}
$os_cancel .= '</select>';

$countries = DB::getInstance()->ExecuteS('SELECT id_country FROM '._DB_PREFIX_.'module_country WHERE id_module = '.($this->id));
foreach ($countries as $country)
$this->country[$country['id_country']] = $country['id_country'];

$exceptions = '';
try {
$token = Configuration::get('PAYNL_TOKEN');
$service_id = Configuration::get('PAYNL_SERVICE_ID');

$service_api = new PayApiGetservice();
$service_api->setApiToken($token);
$service_api->setServiceId($service_id);

$profiles = $service_api->doRequest();
$profiles = $profiles['paymentOptions'];
//var_dump($profiles);



$countries = Country::getCountries(($this->context->cookie->id_lang));

$force_profiles_enable = false;
$profiles_enable = (array_key_exists('PAYNL_COUNTRY_EXCEPTIONS', $conf) ? $conf['PAYNL_COUNTRY_EXCEPTIONS'] : '');
if (Tools::strlen($profiles_enable) == 0)
{
$profiles_enable = array();
$force_profiles_enable = true;
}
else
{
$profiles_enable = unserialize($profiles_enable);
if ($profiles_enable == false)
{
$force_profiles_enable = true;
$profiles_enable = array();
}
}

$profiles_order = (array_key_exists('PAYNL_PAYMENT_METHOD_ORDER', $conf) ? $conf['PAYNL_PAYMENT_METHOD_ORDER'] : '');
$extra_costs = (array_key_exists('PAYNL_PAYMENT_EXTRA_COSTS', $conf) ? $conf['PAYNL_PAYMENT_EXTRA_COSTS'] : '');
$validate_on_start = (array_key_exists('PAYNL_VALIDATE_ON_START', $conf) ? $conf['PAYNL_VALIDATE_ON_START'] : '');

if (Tools::strlen($profiles_order) == 0)

$profiles_order = array();

else
{
$profiles_order = unserialize($profiles_order);
if ($profiles_order == false)

$profiles_order = array();

}

if (Tools::strlen($extra_costs) == 0)

$extra_costs = array();

else
{
$extra_costs = unserialize($extra_costs);
if ($extra_costs == false)

$extra_costs = array();

}
if (Tools::strlen($validate_on_start) == 0)

$validate_on_start = array();

else
{
$validate_on_start = unserialize($validate_on_start);
if ($validate_on_start == false)

$validate_on_start = array();

}

$exceptions = '<br /><h2 class="space">'.$this->l('Payment restrictions').'</h2>';
$exceptions .= '<table border="1"><tr><th>'.$this->l('Country').'</th><th colspan="'.count($profiles).'">'.$this->l('Payment methods').'</th></tr>';
$exceptions .= '<tr><td>&nbsp;</td>';
foreach ($profiles as $profile)

$exceptions .= '<td>'.$profile['name'].'</td>';

$exceptions .= '</tr>';

foreach ($countries as $countryid => $country)
{
if (!Tools::getIsset($this->country[$countryid]))

continue;

$exceptions .= '<tr><td>'.$country['name'].'</td>';

foreach ($profiles as $profile)
{
$exceptions .= '<td>';

if (!$force_profiles_enable)
{

$exceptions .= '<input type="checkbox" name="enaC['.$countryid.']['.$profile['id'].']" value="'.$profile['name'].'"'
.(Tools::getIsset($profiles_enable[$countryid][$profile['id']]) ? ' checked="checked"' : '').' />';
}
else

$exceptions .= '<input type="checkbox" name="enaC['.$countryid.']['.$profile['id'].']" value="'.$profile['name'].'" checked="checked" />';

$exceptions .= '</td>';
}
$exceptions .= '</tr>';
}
$exceptions .= '</table>';

$exceptions .= '<br /><h2 class="space">'.$this->l('Payment priority').'</h2>';
$exceptions .= '<p>'.$this->l('Lower priority is more important').'</p>';
$exceptions .= '<table border="1"><tr><th>'.$this->l('Payment method').'</th><th>'.$this->l('Order').'</th>';
$exceptions .= '<th>'.$this->l('Extra costs fixed').'</th>';
$exceptions .= '<th>'.$this->l('Extra costs percentage').'</th>';
$exceptions .= '<th>'.$this->l('Extra costs max').'</th>';
$exceptions .= '<th>'.$this->l('Validate on transaction start').'</th>';
$exceptions .= '</tr>';
foreach ($profiles as $profile)
{
$exceptions .= '<tr><td>'.$profile['name'].'</td><td>';

$exceptions .= '<select name="enaO['.$profile['id'].']">';
$value = '';
if (Tools::getIsset($profiles_order[$profile['id']]))

$value = $profiles_order[$profile['id']];

$value_amount = count($profiles);
for ($i = 0; $i < $value_amount; $i++)
{
$selected = '';
if ($value == $i)

$selected = 'selected="selected"';

$exceptions .= '<option value="'.$i.'" '.$selected.'>'.$this->l('Priority').' '.($i + 1).'</option>';
}
$exceptions .= '</select>';
$exceptions .= '</td>';

$fixed = $extra_costs[$profile['id']]['fixed'];
$percentage = $extra_costs[$profile['id']]['percentage'];
$max = $extra_costs[$profile['id']]['max'];

$exceptions .= '<td><input name="payExtraCosts['.$profile['id'].'][fixed]" type="text" value="'.$fixed.'" /></td>';
$exceptions .= '<td><input name="payExtraCosts['.$profile['id'].'][percentage]"  type="text" value="'.$percentage.'" /></td>';
$exceptions .= '<td><input name="payExtraCosts['.$profile['id'].'][max]"  type="text" value="'.$max.'" /></td>';

$validate_on_start_checked = '';
if (Tools::getIsset($validate_on_start[$profile['id']]) && $validate_on_start[$profile['id']] == 1)

$validate_on_start_checked = "checked='checked'";

$exceptions .= '<td><input type="hidden" name="validateOnStart['.$profile['id'].']" value="0" />
<input '.$validate_on_start_checked.' name="validateOnStart['.$profile['id'].']"  type="checkbox" value="1" /></td>';

$exceptions .= '</tr>';
}
$exceptions .= '</table>';
} catch (Exception $ex) {
$exceptions = '<br/><h2 class="space">$this->l("Payment restrictions")
</h2><br />$this->l("Payment restrictions available after connecting to Pay.nl")';
}

$this->_html .= '
<form action="'.$_SERVER['REQUEST_URI'].'" method="post">
<fieldset>
  <legend><img src="../img/admin/contact.gif" />'.$this->l('Settings').'</legend>
  
  <label>'.$this->l('Token').'</label>
  <div class="margin-form"><input type="text" size="33" name="paynltoken" value="'.htmlentities($paynltoken, ENT_COMPAT, 'UTF-8').'" /></div>
  <label>'.$this->l('Service ID').'</label>
  <div class="margin-form"><input type="text" size="33" name="service_id" value="'.htmlentities($service_id, ENT_COMPAT, 'UTF-8').'" /></div>
  <br>
  <hr>
  <br>
  <label>'.$this->l('Pending').'</label>
  <div class="margin-form">'.$os_wait.' Alleen van toepassing op betalingen waarbij extra kosten worden
gerekend, de status gaat daarna meteen naar success</div>
  <label>'.$this->l('Success').'</label>
  <div class="margin-form">'.$os_success.'</div>
  <label>'.$this->l('Cancel').'</label>
  <div class="margin-form">'.$os_cancel.'</div>
  <br />'
.$exceptions.
'<br /><center><input type="submit" name="submitPaynl" value="'.$this->l('Update settings').'" class="button" /></center>
</fieldset>
</form><br /><br />';
}

protected function validateOrderData()
{
return true;
}

}
