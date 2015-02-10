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

class paynl_paymentmethodsExchangeModuleFrontController extends ModuleFrontController
{
	public function initContent()
	{
            $transactionId = Tools::getValue('order_id');
            $action = Tools::getValue('action');
           
            try{
                if(strpos($action, 'refund') !== false){
                    throw new Pay_Exception('Ignoring refund');
                }
                if(strpos($action, 'pending') !== false){
                    throw new Pay_Exception('Ignoring pending');
                }
                $result = Pay_Helper_Transaction::processTransaction($transactionId);
            } catch (Exception $ex) {
                echo "TRUE| ";
                echo $ex->getMessage();
                die();
            }
            echo 'TRUE| Status updated to '.$result['state']. ' for cartId: '.$result['orderId'].' orderId: '.@$result['real_order_id'];
            die();
	}
}
