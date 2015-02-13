<?php
/**
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
 */

class PaynlPaymentmethodsPaymentModuleFrontController extends ModuleFrontController {

public $ssl = true;
public $display_column_left = false;

/**
* @see FrontController::initContent()
*/
public function initContent()
{
//parent::initContent();

$cart = $this->context->cart;

$delivery_address = new Address((int)$cart->id_address_delivery);
$invoice_address = new Address((int)$cart->id_address_invoice);
$payment_option_id = Tools::getValue('pid');

$token = Configuration::get('PAYNL_TOKEN');
$service_id = Configuration::get('PAYNL_SERVICE_ID');
$status_pending = Configuration::get('PAYNL_WAIT');

if (!isset($cart->id))
{
echo "Can't find cart";
exit();
}
try {
//validate the order
$customer = new Customer($cart->id_customer);
$total = (float)$cart->getOrderTotal(true, Cart::BOTH);

//$orderStatus = Configuration::get('PAYNL_WAIT');
$module = $this->module;

$$currency_id = $this->context->currency->id;

$currency_code = $this->context->currency->iso_code;

//$paymentMethodName = $module->getPaymentMethodName($payment_option_id);

$extra_fee = $module->getExtraCosts($payment_option_id, $total);

$total += $extra_fee;
//$cart->additional_shipping_cost = $extra_fee;


//$module->validateOrderPay((int)$cart->id, $orderStatus, $total, $extra_fee, $module->getPaymentMethodName
//($payment_option_id), null, array(), (int)$$currency_id, false, $customer->secure_key);

$cart_id = $cart->id;

$api_start = new PayApiStart();

//Klantgegevens meesturen
/* array(
*  initals
*  lastName
*  language
*  accessCode
*  gender (M or F)
*  dob (DD-MM-YYYY)
*  phoneNumber
*  emailAddress
*  bankAccount
*  iban
*  bic
*  sendConfirmMail
*  confirmMailTemplate
*  address => array(
*      streetName
*      streetNumber
*      zipCode
*      city
*      countryCode
*  )
*  invoiceAddress => array(
*      initials
*      lastname
*      streetName
*      streetNumber
*      zipCode
*      city
*      countryCode
*  )
* )
*/
$arr_enduser = array();
$arr_enduser['initials'] = $customer->firstname;
$arr_enduser['lastName'] = $customer->lastname;

list($year,$month,$day) = explode('-', $customer->birthday);
$arr_enduser['dob'] = $day.'-'.$month.'-'.$year;

$arr_enduser['emailAddress'] = $customer->email;

// delivery address
$arr_address = array();
$str_address = $delivery_address->address1.$delivery_address->address2;
$arr_street_house_nr = Pay_Helper::splitAddress($str_address);
$arr_address['streetName'] = $arr_street_house_nr[0];
$arr_address['streetNumber'] = $arr_street_house_nr[1];
$arr_address['zipCode'] = $delivery_address->postcode;
$arr_address['city'] = $delivery_address->city;
$country = new Country($delivery_address->id_country);
$arr_address['countryCode'] = $country->iso_code;

$arr_enduser['address'] = $arr_address;

// invoice address
$arr_address = array();
$arr_address['initials'] = $customer->firstname;
$arr_address['lastName'] = $customer->lastname;

$str_address = $invoice_address->address1.$invoice_address->address2;
$arr_street_house_nr = Pay_Helper::splitAddress($str_address);
$arr_address['streetName'] = $arr_street_house_nr[0];
$arr_address['streetNumber'] = $arr_street_house_nr[1];
$arr_address['zipCode'] = $invoice_address->postcode;
$arr_address['city'] = $invoice_address->city;
$country = new Country($invoice_address->id_country);
$arr_address['countryCode'] = $country->iso_code;
$arr_enduser['invoiceAddress'] = $arr_address;

$api_start->setEnduser($arr_enduser);

// producten toevoegen
$products = $cart->getProducts();

foreach ($products as $product)
$api_start->addProduct($product['id_product'], $product['name'], round($product['price_wt'] * 100), $product['cart_quantity'], 'H');
//verzendkosten toevoegen
$shipping_cost = $cart->getTotalShippingCost();
if ($shipping_cost != 0)

$api_start->addProduct('SHIPPING', 'Verzendkosten', round($shipping_cost * 100), 1, 'H');

if ($extra_fee != 0)

$api_start->addProduct('PAYMENTFEE', 'Betaalkosten', round($extra_fee * 100), 1, 'H');

$api_start->setApiToken($token);
$api_start->setServiceId($service_id);
$api_start->setDescription($cart->id);
$api_start->setExtra1('CartId: '.$cart->id);
//$api_start->setExtra2();

$api_start->setPaymentOptionId($payment_option_id);

$finish_url = Context::getContext()->link->getModuleLink('paynl_paymentmethods', 'return');
$exchange_url = Context::getContext()->link->getModuleLink('paynl_paymentmethods', 'exchange');
$api_start->setFinishUrl($finish_url);
$api_start->setExchangeUrl($exchange_url);

$api_start->setAmount(round($total * 100));

$api_start->setCurrency($currency_code);

$result = $api_start->doRequest();

$start_data = $api_start->getPostData();

Pay_Helper_Transaction::addTransaction($result['transaction']['transactionId'], $payment_option_id,
round($total * 100), $currency_code, $cart_id, $start_data);

if ($this->module->validateOnStart($payment_option_id))

$module->validateOrderPay((int)$cart->id, $status_pending, $total, $extra_fee, $module->getPaymentMethodName
($payment_option_id), null, array('transaction_id' => $result['transaction']['transactionId']), (int)$$currency_id, false, $customer->secure_key);

Tools::redirect($result['transaction']['paymentURL']);
//$url = $paynl->startTransaction($cart);
}
catch (Exception $e)
{
echo $e->getMessage();
}
//betaling starten
}
}
