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

class PayApiInfo extends PayApi {

/**
*
* @var string The version of the api
*/
protected $_version = 'v3';
/**
*
* @var string The controller of the api
*/
protected $_controller = 'transaction';
/**
*
* @var string The action
*/
protected $_action = 'info';

/**
* Set the transaction id for the request
*
* @param string $transactionId
*/
public function setTransactionId($transactionId)
{
$this->_postData['transactionId'] = $transactionId;
}
/**
* Check if all required fields are set, if all required fields are set, returns the fields
*
* @return array The data to post
* @throws Pay_Exception
*/
protected function _getPostData()
{
$data = parent::_getPostData();
if ($this->_apiToken == '')

throw new PayException('apiToken not set', 1);
else

$data['token'] = $this->_apiToken;

if (!isset($this->_postData['transactionId']))
throw new PayException('transactionId is not set', 1);

return $data;
}
}
