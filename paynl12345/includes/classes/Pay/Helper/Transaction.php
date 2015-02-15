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

class PayHelperTransaction {

public static function addTransaction($transaction_id, $option_id, $amount, $currency, $order_id, $start_data)
{
$db = Db::getInstance();

$data = array(
'transaction_id' => $transaction_id,
'option_id' => (int)$option_id,
'amount' => (int)$amount,
'currency' => $currency,
'order_id' => $order_id,
'start_data' =>  $db->escape(Tools::json_encode($start_data)),
);

$db->insert('pay_transactions', $data);
}

private static function updateTransactionState($transaction_id, $status_text)
{
$db = Db::getInstance();

$db->update('pay_transactions', array('status' => $status_text), "transaction_id = '".$db->escape($transaction_id)."'");
}

public static function getTransaction($transaction_id)
{
$db = Db::getInstance();

$sql = 'SELECT * FROM "'._DB_PREFIX_.'pay_transactions WHERE transaction_id = "'.$db->escape($transaction_id).'""';

$row = $db->getRow($sql);
if (empty($row))

throw new PayException('Transaction not found');

return $row;
}

/**
 * Check if the order is already paid, it is possible that an order has more than 1 transaction.
 * So we heck if another transaction for this order is already paid
 * 
 * @param integer $order_id
 */
public static function orderPaid($order_id)
{
$db = Db::getInstance();

$sql = 'SELECT * FROM "._DB_PREFIX_."pay_transactions WHERE order_id = "'.$db->escape($order_id).'" AND status = "PAID"';

$row = $db->getRow($sql);
if (empty($row))

return false;
else

return true;

}

public static function processTransaction($transaction_id)
{
$token = Configuration::get('PAYNL_TOKEN');
$service_id = Configuration::get('PAYNL_SERVICE_ID');

$api_info = new PayApiInfo();

$api_info->setApiToken($token);
$api_info->setServiceId($service_id);
$api_info->setTransactionId($transaction_id);

$result = $api_info->doRequest();
$transaction_amount = $result['paymentDetails']['paidAmount'];

$state_id = $result['paymentDetails']['state'];

$state_text = self::getStateText($state_id);

//de transactie ophalen
try{
$transaction = self::getTransaction($transaction_id);
} catch (PayException $ex) {
// transactie is niet gevonden... quickfix, we voegen hem opnieuw toe
self::addTransaction($transaction_id, $result['paymentDetails']['paymentOptionId'],
$result['paymentDetails']['amount'], $result['paymentDetails']['paidCurrency'],
str_replace('CartId: ', '', $result['statsDetails']['extra1']), 'Inserted after not found');

$transaction = self::getTransaction($transaction_id);
}

$cart_id = $order_id = $transaction['order_id'];

$order_paid = self::orderPaid($order_id);

if ($order_paid == true && $state_text != 'PAID')

throw new PayException('Order already paid');

if ($state_text == $transaction['status'])
{
//nothing changed so return without changing anything
$real_order_id = Order::getOrderByCartId($order_id);
return array(
'orderId' => $order_id,
'state' => $state_text,
'real_order_id' => $real_order_id,
);
}

//update the transaction state
self::updateTransactionState($transaction_id, $state_text);

$obj_order = Order::getOrderByCartId($cart_id);
//$obj_order = new Order($order_id);

//$statusPending = Configuration::get('PAYNL_WAIT');
$status_paid = Configuration::get('PAYNL_SUCCESS');
$status_cancel = Configuration::get('PAYNL_CANCEL');

$id_order_state = '';

//$paid = false;

if ($state_text == 'PAID')
{
$id_order_state = $status_paid;

$module = Module::getInstanceByName(Tools::getValue('module'));

$cart = new Cart($cart_id);
$customer = new Customer($cart->id_customer);

$currency = $cart->id_currency;

$order_total = $cart->getOrderTotal();
$extra_fee = $module->getExtraCosts($transaction['option_id'], $order_total);

$cart->additional_shipping_cost += $extra_fee;

$cart->save();

$payment_method_name = $module->getPaymentMethodName($transaction['option_id']);

$module->validateOrderPay((int)$cart->id, $id_order_state, $transaction_amount / 100, $extra_fee,
$payment_method_name, null, array('transaction_id' => $transaction_id), (int)$currency, false, $customer->secure_key);

$real_order_id = Order::getOrderByCartId($cart->id);
}
elseif ($state_text == 'CANCEL')
{
$real_order_id = Order::getOrderByCartId($cart_id);

if ($real_order_id)
{
$obj_order = new Order($real_order_id);
$history = new OrderHistory();
$history->id_order = (int)$obj_order->id;
$history->changeIdOrderState((int)$status_cancel, $obj_order);
$history->addWithemail();
}
}

return array(
'orderId' => $order_id,
'real_order_id' => $real_order_id,
'state' => $state_text,
);
}

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
case 80:
case -51:
return 'CHECKAMOUNT';
case 100:
return 'PAID';
default:
if ($state_id < 0)

return 'CANCEL';
else
return 'PENDING';

}
}

}
