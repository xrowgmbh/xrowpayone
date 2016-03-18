<?php

$Module = & $Params['Module'];
$http = eZHTTPTool::instance();
$tpl = eZTemplate::factory();

if ($http->hasPostVariable( 'txid' ))
{
    $txid = $http->PostVariable( 'txid' );
    $txaction = $http->PostVariable( 'txaction' );

    if( $txaction === "appointed" )
    {
        eZLog::write("PENDING in step 2 ('preauthorisation') ::transaction module call:: for txid " . $txid ." :::::status:::: " . $txaction, $logName = 'xrowpayone.log', $dir = 'var/log');
        $db = eZDB::instance();
        $relevant_order = $db->arrayQuery("SELECT * FROM ezorder where data_text_1 LIKE '%<txid>$txid</txid>%';");

        if( count($relevant_order) == 1 )
        {
                    $order = eZOrder::fetch( $relevant_order[0]["id"] );
                    $doc = new DOMDocument( '1.0', 'utf-8' );
                    $doc->loadXML($order->DataText1);
                    $shop_account_element = $doc->getElementsByTagName('shop_account');

                    $cc3d_sec_elements = $doc->getElementsByTagName('cc3d_reserved');
                    //detecting if its a 3d secure CC so we need to do something if its normal CC transaction then we just do nothing
                    if( $cc3d_sec_elements->length >= 1 )
                    {
                        $db->begin();
                        $cc3d_sec_element = $cc3d_sec_elements->item(0);
                        //change value
                        $cc3d_sec_element->nodeValue = "true";

                        $order->setAttribute( 'data_text_1', $doc->saveXML() );
                        $order->store();
                        $db->commit();
                    }
        }
        else
        {
            eZLog::write("PENDING in step 2 ('preauthorisation') ::transaction module call:: for txid ". $txid . " specific order could not be determined (this is normal if the action was caused by a normal credit card without 3d secure protection!)", $logName = 'xrowpayone.log', $dir = 'var/log');
        }
    }
    elseif( $txaction === "paid" )
    {
        eZLog::write("PENDING in step 3 ('capture') ::transaction module call:: for txid ". $txid . ":::::status:::: " . $txaction , $logName = 'xrowpayone.log', $dir = 'var/log');
    }
    elseif( $txaction === "capture" )
    {
        eZLog::write("PENDING in step 3 ('capture') ::transaction module call:: for txid ". $txid . ":::::status:::: " . $txaction , $logName = 'xrowpayone.log', $dir = 'var/log');
    }
    else
    {
        $tpl->setVariable( 'error', 'ERROR - status' . $txaction );
        eZLog::write("UNKNOWN STATUS: transaction module called for txid " . $txid . " with state " . $txaction, $logName = 'xrowpayone.log', $dir = 'var/log');
    }
}

$Result = array();
$Result['content'] = $tpl->fetch( "design:statuscheck.tpl" );
$Result['pagelayout'] = 'payoneemptylayout.tpl';

?>
