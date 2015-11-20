<?php

class xrowPayoneBaseGateway extends xrowEPaymentGateway
{
    //this function is called when chosing delivered/paid - on success it expects true
    public function capture( eZOrder $order )
    {
        //start the capture transaction
        $response = self::transaction( $order );

        if( $response AND $response !== "" )
        {
            $response_array = array();
            //go through all lines of the result
            foreach(preg_split("/((\r?\n)|(\r\n?))/", $response) as $line)
            {
                //prepare a nice readable array
                $tmp_explode_result = explode("=", $line);
                $response_array[$tmp_explode_result[0]] = $tmp_explode_result[1];
            }
            
            if ( count($response_array) >= 1 AND $response_array["status"] === "APPROVED" )
            {
                eZLog::write("SUCCESS in step 3('capture') for order ID " . $order->ID, $logName = 'xrowpayone.log', $dir = 'var/log');
            }
            else
            {
                eZLog::write("FAILED in step 3('capture') for order ID " . $order->ID . " with ERRORCODE " . $response_array['errorcode'] . " Message: " . $response_array['errormessage'], $logName = 'xrowpayone.log', $dir = 'var/log');
                //TODO-nice-to-have: add a error message for the editor when the capture was not successfull
                //var_dump($response_array["errorcode"]);
                //var_dump($response_array["errormessage"]);
                die("Umstellung leider nicht möglich. Bitte wenden Sie sich an den Administrator.");
            }
        }
        else
        {
            eZLog::write("ERROR: \$response not set or empty in file " . __FILE__ . " on line " . __LINE__, $logName = 'xrowpayone.log', $dir = 'var/log');
            //TODO-nice-to-have: add a error message for the editor when the capture was not successfull
            //var_dump($response_array["errorcode"]);
            //var_dump($response_array["errormessage"]);
            die("Umstellung leider nicht möglich. Bitte wenden Sie sich an den Administrator.");
        }

        return true;
    }

    static function transaction( $order )
    {
        $payoneINI = eZINI::instance( 'xrowpayone.ini' );

        //fetching settings
        $mid = $payoneINI->variable( 'GeneralSettings', 'MID' );
        $portal_id = $payoneINI->variable( 'GeneralSettings', 'PortalID' );
        $mode = $payoneINI->variable( 'GeneralSettings', 'Mode' );
        $key = $payoneINI->variable( 'GeneralSettings', 'Key' );
        $algorithm = $payoneINI->variable( 'GeneralSettings', 'Algorithm' );
        $api_version = $payoneINI->variable( 'GeneralSettings', 'APIVersion' );
        $encoding = $payoneINI->variable( 'GeneralSettings', 'Encoding' );

        //prepare some parameter values
        $key = md5($key);
        $order_total_in_cent = (string)$order->totalIncVAT()*100;
        $currency_code = $order->currencyCode();
        $order_xml = simplexml_load_string($order->DataText1);
        $txid = (string)$order_xml->txid;

        if ( !isset($txid) OR $txid == "" )
        {
            eZLog::write("ERROR: \$txid not set or empty in file " . __FILE__ . " on line " . __LINE__, $logName = 'xrowpayone.log', $dir = 'var/log');
            return false;
        }

        //create param array
        $param_array["mid"] = $mid;
        $param_array["portalid"] = $portal_id;
        $param_array["key"] = $key;
        $param_array["api_version"] = $api_version;
        $param_array["mode"] = $mode;
        $param_array["request"] = "capture";
        $param_array["encoding"] = $encoding;
        $param_array["txid"] = $txid;
        $param_array["amount"] = $order_total_in_cent;
        $param_array["currency"] = $currency_code;
        
        //sort params in alphabetic order
        ksort($param_array);
        
        $parameter_string = "";
        foreach( $param_array as $key => $parameter )
        {
            $parameter_string .= $key . "=". $parameter . "&";
        }

        $url = "https://api.pay1.de/post-gateway/";
        $result = false;

        if ( function_exists( 'curl_init' ) )
        {
            //open connection
            $ch = curl_init();

            //set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, count($param_array));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $parameter_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            //execute post
            $result = curl_exec($ch);
            $info = curl_getinfo( $ch );
            if ( $info['http_code'] != 200 )
            {
                eZLog::write("ERROR: Could not reach payone server (" . $url . ") during capture process in file " . __FILE__ . " on line " . __LINE__, $logName = 'xrowpayone.log', $dir = 'var/log');
            }

            //close connection
            curl_close( $ch );
        }
        else
        {
            eZLog::write("ERROR: Function 'curl_init' not found in " . __FILE__ . " on line " . __LINE__, $logName = 'xrowpayone.log', $dir = 'var/log');
        }
        return $result;
    }
}

?>