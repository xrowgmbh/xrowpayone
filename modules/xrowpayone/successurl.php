<?php

$Module = & $Params['Module'];
$http = eZHTTPTool::instance();
#$namedParameters = $Module->NamedParameters;
#$tpl = eZTemplate::factory();
$db = eZDB::instance();
#$current_user = eZUser::currentUser();
#$user_id = $current_user->ContentObjectID;
#$xrowForumINI = eZINI::instance( 'xrowforum.ini' );
#$user_class_id = $xrowForumINI->variable( 'ClassIDs', 'User' );
#$tpl->setVariable( 'success', $success );

$order_id = $Params["orderID"];
$siteaccess = $Params["siteaccess"];

if( isset($order_id) )
{
    eZLog::write("PENDING in step 2 ('preauthorisation') ::3D Secure Card password ACCEPTED :: for order ID " . $order_id, $logName = 'xrowpayone.log', $dir = 'var/log');

    $paymentObj = xrowPaymentObject::fetchByOrderID( $orderID );
    $paymentObj->approve();
    $paymentObj->store();

    if( isset($siteaccess) )
    {
        $Module->redirectTo( '/shop/checkout/' );
    }
    else
    {
        $Module->redirectTo( '/' . $siteaccess . '/shop/checkout/' );
    }

    eZLog::write("PENDING in step 2 ('preauthorisation') :: sending to order confirmation :: for order ID " . $order_id, $logName = 'xrowpayone.log', $dir = 'var/log');
}
else
{
    //TODO: error!
}

$Result = array();
#$Result['content'] = $tpl->fetch( 'design:pm/network.tpl' );
#$Result['path'] = array( array( 'url' => "/",
#                                'text' => ezpI18n::tr( 'extension/xrowpm', 'Home' ) ),
#                         array( 'url' => false,
#                                'text' => ezpI18n::tr( 'extension/xrowpm', 'My Network' ) ) );
    
?>