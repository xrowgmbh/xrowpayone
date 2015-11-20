<?php

class xrowPayoneCreditCardGateway extends xrowPayoneBaseGateway
{
    const GATEWAY_STRING = 'xrowPayoneCreditCard';
    const TEMPLATE = 'design:workflow/payone_creditcard.tpl';

    function name()
    {
        return ezpI18n::tr( 'extension/xrowpayone', 'Credit Card' );
    }

    function execute( $process, $event )
    {
        $http = eZHTTPTool::instance();
        $shopINI = eZINI::instance( 'shop.ini' );
        $payoneINI = eZINI::instance( 'xrowpayone.ini' );
        $processParams = $process->attribute( 'parameter_list' );

        //get the current order
        $order_id = $processParams['order_id'];
        $order = eZOrder::fetch( $order_id );

        //STEP 2: preauthorisation
        if ( $http->hasPostVariable( 'pseudocardpan' ) )
        {
            //fetching settings
            $pseudocardpan = $http->postVariable( 'pseudocardpan' );
            $aid = $payoneINI->variable( 'GeneralSettings', 'AID' );
            $mid = $payoneINI->variable( 'GeneralSettings', 'MID' );
            $portal_id = $payoneINI->variable( 'GeneralSettings', 'PortalID' );
            $mode = $payoneINI->variable( 'GeneralSettings', 'Mode' );
            $key = $payoneINI->variable( 'GeneralSettings', 'Key' );
            $algorithm = $payoneINI->variable( 'GeneralSettings', 'Algorithm' );
            $api_version = $payoneINI->variable( 'GeneralSettings', 'APIVersion' );

            //prepare some parameter values
            $order_total_in_cent = (string)$order->totalIncVAT()*100;
            $currency_code = $order->currencyCode();
            $order_xml = simplexml_load_string($order->DataText1);
            $country_alpha3 = (string)$order_xml->country;
            $country = eZCountryType::fetchCountry( $country_alpha3, "Alpha3" );
            $country_alpha2 = $country["Alpha2"];
            $last_name = (string)$order_xml->last_name;

            //create hash array
            $hash_array["aid"] = $aid;
            $hash_array["mid"] = $mid;
            $hash_array["portalid"] = $portal_id;
            $hash_array["api_version"] = $api_version;
            $hash_array["mode"] = $mode;
            $hash_array["request"] = "preauthorization";
            $hash_array["responsetype"] = "JSON";
            $hash_array["clearingtype"] = "cc";
            $hash_array["reference"] = $order_id;
            $hash_array["amount"] = $order_total_in_cent;
            $hash_array["currency"] = $currency_code;
            //please note: country, lastname and pseudocardpan dont need to be added to the hash because they are not allwoed (p.25 client doc)

            //create param array
            $param_array["aid"] = $aid;
            $param_array["mid"] = $mid;
            $param_array["portalid"] = $portal_id;
            $param_array["api_version"] = $api_version;
            $param_array["mode"] = $mode;
            $param_array["request"] = "preauthorization";
            $param_array["responsetype"] = "JSON";
            $param_array["hash"] = xrowPayoneHelper::generate_hash( $algorithm, $hash_array, $key );
            $param_array["clearingtype"] = "cc";
            $param_array["reference"] = $order_id;
            $param_array["amount"] = $order_total_in_cent;
            $param_array["currency"] = $currency_code;
            $param_array["lastname"] = $last_name;
            $param_array["country"] = $country_alpha2;
            $param_array["pseudocardpan"] = $pseudocardpan;
            
            //sort params in alphabetic order
            ksort($param_array);
            
            $parameter_string = "?";
            foreach( $param_array as $key => $parameter )
            {
                $parameter_string .= $key . "=". $parameter . "&";
            }
            
            $url = "https://secure.pay1.de/client-api" . $parameter_string;
            
            $json_response = file_get_contents($url);
            if( $json_response )
            {
                $json_response = json_decode($json_response);
                if( $json_response->status != "ERROR" AND isset($json_response->txid) )
                {
                    //get 'txid' from response and keep it
                    $txid = $json_response->txid;
                    
                    //now store it into the order
                    $db = eZDB::instance();
                    $db->begin();
                    $doc = new DOMDocument( '1.0', 'utf-8' );
                    $doc->loadXML($order->DataText1);
                    $shop_account_element = $doc->getElementsByTagName('shop_account');
                    $shop_account_element = $shop_account_element->item(0);
                    
                    //first the TXID
                    $txidNode = $doc->createElement( "txid", $txid );
                    $shop_account_element->appendChild( $txidNode );
                    
                    //then the payment method
                    $paymentmethod = $doc->createElement( xrowECommerce::ACCOUNT_KEY_PAYMENTMETHOD, xrowPayoneCreditCardGateway::GATEWAY_STRING );
                    $shop_account_element->appendChild( $paymentmethod );
    
                    //then the pseudocardpan
                    if ( $http->hasPostVariable( 'truncatedcardpan' ) )
                    {
                        $truncatedcardpan_node = $doc->createElement( "truncatedcardpan", $http->postVariable( 'truncatedcardpan' ) );
                        $shop_account_element->appendChild( $truncatedcardpan_node );
                    }
    
                    //store it
                    $order->setAttribute( 'data_text_1', $doc->saveXML() );
                    $order->store();
                    $db->commit();
    
                    return eZWorkflowType::STATUS_ACCEPTED;
                }
                else
                {
                    //TODO: errorhandling?
                    //bsp 911
                    $errorcode = $json_response->errorcode;
                    //bsp Reference number already exists
                    $errormessage = $json_response->errormessage;
                    //bsp An error occured while processing this transaction (wrong parameters).
                    $customermessage = $json_response->customermessage;
                    var_dump($url);
                    var_dump($errorcode);
                    var_dump($errormessage);
                    var_dump($customermessage);
                    die("fehler aufgerteten");
                }
            }
            else
            {
                //TODO FEHLER remote content nicht gefunden 
            }
        }

        $errors = array();
        $process->Template = array();
        $process->Template['templateName'] = xrowPayoneCreditCardGateway::TEMPLATE;
        $process->Template['path'] = array( array( 'url' => false, 'text' => ezpI18n::tr( 'extension/xrowpayone', 'Payment Information' ) ) );
        $process->Template['templateVars'] = array(
                'errors' => $errors,
                'order' => $order,
                'event' => $event
        );

        // return eZWorkflowType::STATUS_REJECTED;
        return eZWorkflowType::STATUS_FETCH_TEMPLATE_REPEAT;
    }

}

xrowEPayment::registerGateway( xrowPayoneCreditCardGateway::GATEWAY_STRING, "xrowpayonecreditcardgateway", ezpI18n::tr( 'extension/xrowpayone', 'Credit Card' ) );

?>