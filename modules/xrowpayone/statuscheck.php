<?php

$Module = & $Params['Module'];
$http = eZHTTPTool::instance();

eZLog::write("transaction aufgerufen", $logName = 'xrowpayone.log', $dir = 'var/log');

if ($http->hasPostVariable( 'txid' ))
{
    $txid = $http->PostVariable( 'txid' );
    //naechste zeile wird gebraucht?
    $txaction = $http->PostVariable( 'txaction' );
    $txstatus = $http->PostVariable( 'transaction_status' );

    eZLog::write("transaction aufgerufen " . $txid ." :::::mit status:::: " . $txstatus . "( " . $txaction . " )", $logName = 'xrowpayone.log', $dir = 'var/log');

    if( $txstatus === "completed" )
    {
        $db = eZDB::instance();
        $relevant_order = $db->arrayQuery("SELECT * FROM ezorder where data_text_1 LIKE '%<txid>$txid</txid>%';");

        if( count($relevant_order) == 1 )
        {
                    $order = eZOrder::fetch( $relevant_order[0]["id"] );
                    $doc = new DOMDocument( '1.0', 'utf-8' );
                    $doc->loadXML($order->DataText1);
                    $shop_account_element = $doc->getElementsByTagName('shop_account');

                    $cc3d_sec_elements = $doc->getElementsByTagName('cc3d_reserved');
                    //detecting if its a 3d secure CC so we need to do something
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
            eZLog::write("transaction call problem on ". $txid . " specific order could not be determined", $logName = 'xrowpayone.log', $dir = 'var/log');
        }
    }
    else
    {
        eZLog::write("transaction called for txid " . $txid . " but its pending still", $logName = 'xrowpayone.log', $dir = 'var/log');
    }
}

$Result = array();
$Result['pagelayout'] = 'payoneemptylayout.tpl';

?>
