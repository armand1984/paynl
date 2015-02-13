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

class PayApiStart extends PayApi {

protected $version = 'v3';
protected $controller = 'transaction';
protected $action = 'start';
private $amount;
private $currency;
private $payment_option_id;
private $payment_option_sub_id;
private $finish_url;

private $exchange_url;
private $description;
private $enduser;
private $extra1;
private $extra2;
private $extra3;

private $promotor_id;
private $info;
private $tool;
private $object;
private $domain_id;
private $transfer_data;

private $products = array();

public function setCurrency($currency)
{
$this->_currency = Tools::strtoupper($currency);
}
public function setPromotorId($promotor_id)
{
$this->promotor_id = $promotor_id;
}
public function setInfo($info)
{
$this->_info = $info;
}
public function setTool($tool)
{
$this->_tool = $tool;
}
public function setObject($object)
{
$this->_object = $object;
}

public function setTransferData($transfer_data)
{
$this->transfer_data = $transfer_data;
}
/**
* Add a product to an order
* Attention! This is purely an adminstrative option, the amount of the order is not modified.
*
* @param string $id
* @param string $description
* @param int $price
* @param int $quantity
* @param int $vat_percentage
* @throws PayException
*/
public function addProduct($id, $description, $price, $quantity, $vat_percentage = 'H')
{
if (!is_numeric($price))

throw new PayException('Price moet numeriek zijn', 1);

if (!is_numeric($quantity))
throw new PayException('Quantity moet numeriek zijn', 1);

$quantity = $quantity * 1;

//description mag maar 45 chars lang zijn
$description = Tools::substr($description, 0, 45);

$arr_product = array(
'productId' => $id,
'description' => $description,
'price' => $price,
'quantity' => $quantity,
'vatCode' => $vat_percentage,
);
$this->_products[] = $arr_product;
}

/**
* Set the enduser data in the following format
*
* array(
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
*  streetName
*  streetNumber
*  zipCode
*  city
*  countryCode
*  )
*  invoiceAddress => array(
*  initials
*  lastname
*  streetName
*  streetNumber
*  zipCode
*  city
*  countryCode
*  )
* )
* @param array $enduser
*/
public function setEnduser($enduser)
{
$this->_enduser = $enduser;
}

/**
* Set the amount(in cents) of the transaction
*
* @param int $amount
* @throws PayException
*/
public function setAmount($amount)
{
if (is_numeric($amount))

$this->_amount = $amount;
else

throw new PayException('Amount is niet numeriek', 1);

}

public function setPaymentOptionId($payment_option_id)
{
if (is_numeric($payment_option_id))

$this->payment_option_id = $payment_option_id;

else

throw new PayException('PaymentOptionId is niet numeriek', 1);

}

public function setPaymentOptionSubId($payment_option_sub_id)
{
if (is_numeric($payment_option_sub_id))

$this->payment_option_sub_id = $payment_option_sub_id;

else
throw new PayException('PaymentOptionSubId is niet numeriek', 1);

}

/**
* Set the url where the user will be redirected to after payment.
*
* @param string $finish_url
*/
public function setFinishUrl($finish_url)
{
$this->finish_url = $finish_url;
}

/**
* Set the comunication url, the pay.nl server will call this url when the status of the transaction changes
*
* @param string $exchange_url
*/
public function setExchangeUrl($exchange_url)
{
$this->exchange_url = $exchange_url;
}



public function setExtra1($extra1)
{
$this->_extra1 = $extra1;
}
public function setExtra2($extra2)
{
$this->_extra2 = $extra2;
}

public function setExtra3($extra3)
{
$this->_extra3 = $extra3;
}
public function setDomainId($domain_id)
{
$this->domain_id = $domain_id;
}

/**
 * Set the description for the transaction
 * @param type $description
 */
public function setDescription($description)
{
$this->_description = $description;
}

/**
 * Get the post data, if not all required variables are set, this wil rthrow an exception
 * 
 * @return array
 * @throws PayException
 */
protected function GetPostData()
{
$data = parent::GetPostData();

if ($this->api_token == '')

throw new PayException('apiToken not set', 1);
else

$data['token'] = $this->api_token;

if (empty($this->service_id))
throw new PayException('apiToken not set', 1);
else
$data['serviceId'] = $this->service_id;

if (empty($this->_amount))
throw new PayException('Amount is niet geset', 1);
else
$data['amount'] = $this->_amount;

if (!empty($this->_currency))
$data['transaction']['currency'] = $this->_currency;

if (!empty($this->payment_option_id))
$data['paymentOptionId'] = $this->payment_option_id;

if (empty($this->finish_url))
throw new PayException('FinishUrl is niet geset', 1);
else
$data['finishUrl'] = $this->finish_url;

if (!empty($this->exchange_url))
$data['transaction']['orderExchangeUrl'] = $this->exchange_url;

if (!empty($this->_description))
$data['transaction']['description'] = $this->_description;

if (!empty($this->payment_option_sub_id))
$data['paymentOptionSubId'] = $this->payment_option_sub_id;

$data['ipAddress'] = $_SERVER['REMOTE_ADDR'];

// I set the browser data with dummydata, because most servers dont have the get_browser function available
$data['browserData'] = array(
'browser_name_regex' => '^mozilla/5\.0 (windows; .; windows nt 5\.1; .*rv:.*) gecko/.* firefox/0\.9.*$',
'browser_name_pattern' => 'Mozilla/5.0 (Windows; ?; Windows NT 5.1; *rv:*) Gecko/* Firefox/0.9*',
'parent' => 'Firefox 0.9',
'platform' => 'WinXP',
'browser' => 'Firefox',
'version' => 0.9,
'majorver' => 0,
'minorver' => 9,
'cssversion' => 2,
'frames' => 1,
'iframes' => 1,
'tables' => 1,
'cookies' => 1,
);
if (!empty($this->_products))
{
$data['saleData']['invoiceDate'] = date('d-m-Y');
$data['saleData']['deliveryDate'] = date('d-m-Y', strtotime('+1 day'));
$data['saleData']['orderData'] = $this->_products;
}

if (!empty($this->_enduser))

$data['enduser'] = $this->_enduser;

if (!empty($this->_extra1))

$data['statsData']['extra1'] = $this->_extra1;

if (!empty($this->_extra2))
$data['statsData']['extra2'] = $this->_extra2;

if (!empty($this->_extra3))
$data['statsData']['extra3'] = $this->_extra3;

if (!empty($this->promotor_id))
$data['statsData']['promotorId'] = $this->promotor_id;

if (!empty($this->_info))
$data['statsData']['info'] = $this->_info;

if (!empty($this->_tool))
$data['statsData']['tool'] = $this->_tool;

if (!empty($this->_object))
$data['statsData']['object'] = $this->_object;

if (!empty($this->domain_id))
$data['statsData']['domain_id'] = $this->domain_id;

if (!empty($this->transfer_data))
$data['statsData']['transferData'] = $this->transfer_data;

return $data;
}

}
