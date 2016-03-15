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

    eZLog::write("transaction aufgerufen" . $txid ." :::::mit status:::: " . $http->PostVariable( 'txaction' ), $logName = 'xrowpayone.log', $dir = 'var/log'); 

    if( $txstatus === "completed" )
    {
        //fetch order anhand der txid
        //order auf true setzen
    }
    else
    {
        //log pending state
    }
}

$Result = array();

die("ende erreicht");

?>
