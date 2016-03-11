<?php

$Module = & $Params['Module'];
$order_id = $Params["orderID"];

if( isset($order_id) )
{
    eZLog::write("PENDING in step 2 ('preauthorisation') ::3D Secure Card password ACCEPTED :: for order ID " . $order_id, $logName = 'xrowpayone.log', $dir = 'var/log');

    //create the payment gateway - not the best position but at least it works here :)
    $payment = xrowPaymentObject::createNew( (int)$order_id, xrowPayoneCreditCardGateway::GATEWAY_STRING );
    $payment->store();

    //store paymentobject and approve it (required to finish the order)
    $paymentObj = xrowPaymentObject::fetchByOrderID( $order_id );
    $paymentObj->approve();

    eZLog::write("PENDING in step 2 ('preauthorisation') ::3D Secure Card password REDIRECTING to orderview :: for order ID " . $order_id, $logName = 'xrowpayone.log', $dir = 'var/log');

    //redirect into the shopping process => finishing the order!
    $Module->redirectTo( '/shop/checkout/' );
}
else
{
    return $Module->handleError( 1, 'kernel' );
}

$Result = array();

?>