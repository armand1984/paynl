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

class PaynlPaymentmethodsExchangeModuleFrontController extends ModuleFrontController
{
	public function initContent()
	{
		$transaction_id = Tools::getValue('order_id');
		$action = Tools::getValue('action');

		try  {
			if (strpos($action, 'refund') !== false)
				throw new PayException('Ignoring refund');

if (strpos($action, 'pending') !== false)
throw new PayException('Ignoring pending');

$result = Pay_Helper_Transaction::processTransaction($transaction_id);
}
catch (Exception $ex) {
echo 'TRUE|';
echo $ex->getMessage();
die();
}
echo 'TRUE| Status updated to '.$result['state'].' for cartId: '.$result['orderId'].' orderId: '.@$result['real_order_id'];
die();
	}
}
