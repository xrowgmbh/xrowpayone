<?php

$Module = & $Params['Module'];
$order_id = $Params["orderID"];

if( isset($order_id) )
{
    eZLog::write("PENDING in step 2 ('preauthorisation') ::3D Secure Card password ERROR :: for order ID " . $order_id, $logName = 'xrowpayone.log', $dir = 'var/log');

    //create the payment gateway - not the best position but at least it works here :)
    $payment = xrowPaymentObject::createNew( (int)$order_id, xrowPayoneCreditCardGateway::GATEWAY_STRING );
    $payment->store();

    eZLog::write("PENDING in step 2 ('preauthorisation') ::3D Secure Card password REDIRECTING back to checkout :: for order ID " . $order_id, $logName = 'xrowpayone.log', $dir = 'var/log');

    //redirect into the shopping process => finishing the order!
    $Module->redirectTo( '/shop/checkout/' );
}
else
{
    return $Module->handleError( 1, 'kernel' );
}

$Result = array();

?>