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

class PaynlPaymentmethodsReturnModuleFrontController extends ModuleFrontController {

public function initContent()
{
parent::initContent();
$transaction_id = Tools::getValue('orderId');

try {
$result = Pay_Helper_Transaction::processTransaction($transaction_id);

$order = new Order($result['real_order_id']);
$customer = new Customer($order->id_customer);

$this->context->smarty->assign(array(
'reference_order' => $result['real_order_id'],
'email' => $customer->email,
'id_order_formatted'=> $order->reference,
));

if ($result['state'] == 'PAID')
{
Tools::redirect('index.php?controller=order-confirmation&id_cart=
'.$result['orderId'].'&id_module='.$this->module->id.'&id_order='.$result['real_order_id'].'&key='.$customer->secure_key);

}
if ($result['state'] == 'CHECKAMOUNT')
$this->setTemplate('return_checkamount.tpl');

if ($result['state'] == 'CANCEL')
{
if (!empty($result['real_order_id']))

Tools::redirect('index.php?controller=order&submitReorder=Reorder&id_order='.$result['real_order_id']);
else

Tools::redirect('/order');

}
if ($result['state'] == 'PENDING')

Tools::redirect('index.php?controller=order-confirmation&id_cart='.$result['orderId'].'&id_module=
'.$this->module->id.'&id_order='.$result['real_order_id'].'&key='.$customer->secure_key);

}
catch (Exception $ex) {

echo 'Error: '.$ex->getMessage();
die();
}
}

}
