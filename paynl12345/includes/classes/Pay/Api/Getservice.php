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

class PayApiGetservice extends PayApi {

protected $version = 'v3';
protected $controller = 'transaction';
protected $action = 'getService';

protected function getPostData()
{
$data = parent::getPostData();

// Check if all required variables are set
if ($this->api_token == '')

throw new PayException('apiToken not set', 1);
else

$data['token'] = $this->api_token;

if (empty($this->service_id))

throw new PayException('serviceId not set', 1);
else

$data['serviceId'] = $this->service_id;

return $data;
}
/**
* Process the result
*
* @param array $arr_return
* @return array the result
*/
protected function processResult($arr_return)
{
if (!$arr_return['request']['result'])

return $arr_return;

$arr_return['paymentOptions'] = array();

$country_option_list = $arr_return['countryOptionList'];
unset($arr_return['countryOptionList']);
if (isset($country_option_list) && is_array($country_option_list))
{
foreach ($country_option_list as $str_countr_code => $arr_country)
{
foreach ($arr_country['paymentOptionList'] as $arr_payment_profile)
{

if (!isset($arr_return['paymentOptions'][$arr_payment_profile['id']]))
{
$arr_return['paymentOptions'][$arr_payment_profile['id']] = array(
'id' => $arr_payment_profile['id'],
'name' => $arr_payment_profile['name'],
'visibleName' => $arr_payment_profile['name'],
'img' => $arr_payment_profile['img'],
'path' => $arr_payment_profile['path'],
'paymentOptionSubList' => array(),
'countries' => array(),
);
}

if (!empty($arr_payment_profile['paymentOptionSubList']))

$arr_return['paymentOptions'][$arr_payment_profile['id']]['paymentOptionSubList'] = $arr_payment_profile['paymentOptionSubList'];

$arr_return['paymentOptions'][$arr_payment_profile['id']]['countries'][$str_countr_code] = array(
'id' => $str_countr_code,
'name' => $arr_country['visibleName'],
);
}
}
}
return $arr_return;
}

}
