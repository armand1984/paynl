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

class PayHelper {

/**
* Get the status by statusId
*
* @param int $statusId
* @return string The status
*/
public static function getStateText($state_id)
{
switch ($state_id)
{
case -70:
case -71:
return 'CHARGEBACK';
case -51:
return 'PAID CHECKAMOUNT';
case -81:
return 'REFUND';
case -82:
return 'PARTIAL REFUND';
case 20:
case 25:
case 50:
return 'PENDING';
case 60:
return 'OPEN';
case 75:
case 76:
return 'CONFIRMED';
case 80:
return 'PARTIAL PAYMENT';
case 100:
return 'PAID';
default:
if ($state_id < 0)

return 'CANCEL';
else

return 'UNKNOWN';

}
}

/*remove all empty nodes in an array*/
public static function filterArrayRecursive($array)
{
$new_array = array();
foreach ($array as $key => $value)
{
if (is_array($value))

$value = self::filterArrayRecursive($value);

if (!empty($value))

$new_array[$key] = $value;

}
return $new_array;
}

/**
* Find out if the connection is secure
*
* @return boolean Secure
*/
public static function isSecure()
{
$is_secure = false;
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')

$is_secure = true;
elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
|| !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on')

$is_secure = true;

return $is_secure;
}
/**
* Get the uri of the current script without the filename.
* We use this to generate the return- and exchangeurl
*
* @return string The uri
*/
public static function getUri()
{
if (self::isSecure())

$uri = 'https://';
else
$uri = 'http://';

$uri .= $_SERVER['SERVER_NAME'];

if (!empty($_SERVER['REQUEST_URI']))
{
$uri .= $_SERVER['REQUEST_URI'];
$uri_dir = $uri;
if (Tools::substr($uri, -4) == '.php')

$uri_dir = dirname($uri);

if ($uri_dir != 'http:' && $uri_dir != 'https:')

$uri = $uri_dir;

}

return $uri.'/';
}
public static function splitAddress($str_address)
{
$str_address = trim($str_address);

$a = preg_split('/([0-9]+)/', $str_address, 2, PREG_SPLIT_DELIM_CAPTURE);
$str_street_name = trim(array_shift($a));
$$str_street_number = trim(implode('', $a));

if (empty($str_street_name))
{
// American address notation
$a = preg_split('/([a-zA-Z]{2,})/', $str_address, 2, PREG_SPLIT_DELIM_CAPTURE);

$$str_street_number = trim(array_shift($a));
$str_street_name = implode(' ', $a);
}

return array($str_street_name, $$str_street_number);
}

/**
* Sort the $payment_options by name
*
* @param array $$payment_options
* @return array
*/
public static function paymentOptions($payment_options)
{
uasort($payment_options, 'sort$payment_options');
return $payment_options;
}
}
function paymentOptions($a, $b)
{
return strcmp($a['name'], $b['name']);
}