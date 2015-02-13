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
protected $_action = 'getService';

protected function _getPostData()
{
$data = parent::_getPostData();

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
protected function _processResult($arr_return)
{
if (!$arr_return['request']['result'])

return $arr_return;


$arr_return['paymentOptions'] = array();

$countryOptionList = $arr_return['countryOptionList'];
unset($arr_return['countryOptionList']);
if (isset($countryOptionList) && is_array($countryOptionList))
{
foreach ($countryOptionList as $strCountrCode => $arrCountry)
{
foreach ($arrCountry['paymentOptionList'] as $arrPaymentProfile)
{

if (!isset($arr_return['paymentOptions'][$arrPaymentProfile['id']]))
{
$arr_return['paymentOptions'][$arrPaymentProfile['id']] = array(
'id' => $arrPaymentProfile['id'],
'name' => $arrPaymentProfile['name'],
'visibleName' => $arrPaymentProfile['name'],
'img' => $arrPaymentProfile['img'],
'path' => $arrPaymentProfile['path'],
'paymentOptionSubList' => array(),
'countries' => array(),
);
}

if (!empty($arrPaymentProfile['paymentOptionSubList']))

$arr_return['paymentOptions'][$arrPaymentProfile['id']]['paymentOptionSubList'] = $arrPaymentProfile['paymentOptionSubList'];



$arr_return['paymentOptions'][$arrPaymentProfile['id']]['countries'][$strCountrCode] = array(
'id' => $strCountrCode,
'name' => $arrCountry['visibleName'],
);
}
}
}
return $arr_return;
}

}
