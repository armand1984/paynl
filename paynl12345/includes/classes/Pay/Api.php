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

class PayApi {

const REQUEST_TYPE_POST = 1;
const REQUEST_TYPE_GET = 0;

/**
*
* @var string the url to the pay.nl api
*/
protected $api_url = 'http://rest-api.pay.nl';
/**
*
* @var string The version af the api to use
*/
protected $version = 'v3';
/**
*
* @var string The controller of the api to use, generally this is set by the child class
*/
protected $controller = '';
/**
*
* @var string The action of the api to use, generally this is set by the child class
*/
protected $action = '';

/**
*
* @var string The serviceid
*/
protected $service_id = '';
/**
*
* @var string the API token
*/
protected $api_token = '';

/**
*
* @var int The request type (POST or GET) to use when calling the api. use Pay_Api::REQUEST_TYPE_POST or  Pay_Api::REQUEST_TYPE_GET for this variable
*/
protected $request_type = self::REQUEST_TYPE_GET;

/**
*
* @var array The data to post to the pay.nl server
*/
protected $post_data = array();


/**
* Set the serviceid
* The serviceid always starts with SL- and can be found on: https://admin.pay.nl/programs/programs
* 
* @param string $service_id
*/
public function setServiceId($service_id)
{
$this->service_id = $service_id;
}

/**
* Set the API token
* The API token is used to identify your company.
* The API token can be found on: https://admin.pay.nl/my_merchant on the bottom
* 
* @param string $api_token
*/
public function setApiToken($api_token)
{
$this->api_token = $api_token;
}

protected function getPostData()
{
return $this->post_data;
}

protected function processResult($data)
{
return $data;
}

/**
* Generates the api url
* 
* @return string The full url to the api
* @throws Pay_Exception
*/
private function getApiUrl()
{
if ($this->_version == '')
throw new PayException('version not set', 1);

if ($this->_controller == '') throw new PayException('controller not set', 1);

if ($this->_action == '') throw new PayException('action not set', 1);

return $this->api_url.'/'.$this->_version.'/'.$this->_controller.'/'.$this->_action.'/json/';
}

/**
* Get the data to post to the api for debug use
* 
* @return array The post data
*/
public function getPostData()
{
return $this->getPostData();
}
/**
* Do the request and get the result
* 
* @return array The result
* @throws Pay_Exception On error generated before sending
* @throws Pay_Api_Exception On error returned by the pay.nl api
*/
public function doRequest()
{
if ($this->getPostData())
{
$url = $this->getApiUrl();
$data = $this->getPostData();

			$str_data = http_build_query($data);

$api_url = $url;

if (function_exists('curl_version'))
{
$ch = curl_init();
if ($this->request_type == self::REQUEST_TYPE_GET)

$api_url .= '?'.$str_data;

else
curl_setopt($ch, CURLOPT_POSTFIELDS, $str_data);

curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$result = curl_exec($ch);
curl_close($ch);
}

$arr_result = Tools::json_decode($result, true);

if ($this->validateResult($arr_result)) return $this->_processResult($arr_result);
}
}

/**
* Validate the result and throw an exception when there is an error
* 
* @param array $arr_result The result
* @return boolean Result valid
* @throws Pay_Api_Exception
*/
protected function validateResult($arr_result)
{
if ($arr_result['request']['result'] == 1)

return true;
else
{
if (isset($arr_result['request']['errorId']) && isset($arr_result['request']['errorMessage']))

throw new PayApiException($arr_result['request']['errorId'].' - '.$arr_result['request']['errorMessage']);

elseif (isset($arr_result['error']))

throw new PayApiException($arr_result['error']);
else
throw new PayApiException('Unexpected api result');

}
}
}
