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
        $siteINI = eZINI::instance( 'site.ini' );
        $shopINI = eZINI::instance( 'shop.ini' );
        $payoneINI = eZINI::instance( 'xrowpayone.ini' );
        $processParams = $process->attribute( 'parameter_list' );
        $errors = array();
        $process_id = $process->ID;

        //get the current order
        $order_id = $processParams['order_id'];
        $order = eZOrder::fetch( $order_id );

        //checking if its only a redirect and so the preauthorisation is already finished
        $paymentObj = xrowPaymentObject::fetchByOrderID( $order_id );
        if ( is_object( $paymentObj ) && $paymentObj->approved() )
        {
            //now disapprove again because its 3d CC payment and its only paid when capture is successful
            $paymentObj->reject();
            xrowPayoneCreditCardGateway::setPaymentMethod($order);
            eZLog::write("SUCCESS in step 2 ('preauthorisation') ::3D Secure Card detected - FINISHED :: for order ID " . $order_id, $logName = 'xrowpayone.log', $dir = 'var/log');
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        //STEP 2: preauthorisation
        if ( $http->hasPostVariable( 'pseudocardpan' ) )
        {
            //fetching settings
            $pseudocardpan = $http->postVariable( 'pseudocardpan' );
            $site_url = $siteINI->variable( 'SiteSettings', 'SiteURL' );
            $aid = $payoneINI->variable( 'GeneralSettings', 'AID' );
            $mid = $payoneINI->variable( 'GeneralSettings', 'MID' );
            $portal_id = $payoneINI->variable( 'GeneralSettings', 'PortalID' );
            $mode = $payoneINI->variable( 'GeneralSettings', 'Mode' );
            $key = $payoneINI->variable( 'GeneralSettings', 'Key' );
            $algorithm = $payoneINI->variable( 'GeneralSettings', 'Algorithm' );
            $api_version = $payoneINI->variable( 'GeneralSettings', 'APIVersion' );
            $response_type = $payoneINI->variable( 'GeneralSettings', 'ResponseType' );
            $cc_3d_secure_enabled = $payoneINI->variable( 'CC3DSecure', 'Enabled' );
            $error_url = $payoneINI->variable( 'CC3DSecure', 'ErrorURL' );
            $success_url = $payoneINI->variable( 'CC3DSecure', 'SuccessURL' );
            $siteaccess = $GLOBALS['eZCurrentAccess'];
            $siteaccess = $siteaccess["name"];

            //prepare some parameter values
            $error_url = "https://" . $site_url  . "/" . $siteaccess . "/" . $error_url  . "/orderID/" . $order_id;
            $success_url = "https://" . $site_url  . "/" . $siteaccess . "/" . $success_url  . "/orderID/" . $order_id;
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
            $hash_array["responsetype"] = $response_type;
            $hash_array["clearingtype"] = "cc";
            $hash_array["reference"] = $order_id;
            $hash_array["amount"] = $order_total_in_cent;
            $hash_array["currency"] = $currency_code;
            if( $cc_3d_secure_enabled == "true")
            {
                $hash_array["successurl"] = $success_url;
                $hash_array["errorurl"] = $error_url;
            }
            //please note: country, lastname and pseudocardpan are not needed to be added to the hash because they are not allwoed (p.25 client doc)

            //create param array
            $param_array["aid"] = $aid;
            $param_array["mid"] = $mid;
            $param_array["portalid"] = $portal_id;
            $param_array["api_version"] = $api_version;
            $param_array["mode"] = $mode;
            $param_array["request"] = "preauthorization";
            $param_array["responsetype"] = $response_type;
            $param_array["hash"] = xrowPayoneHelper::generate_hash( $algorithm, $hash_array, $key );
            $param_array["clearingtype"] = "cc";
            $param_array["reference"] = $order_id;
            $param_array["amount"] = $order_total_in_cent;
            $param_array["currency"] = $currency_code;
            $param_array["lastname"] = $last_name;
            $param_array["country"] = $country_alpha2;
            $param_array["pseudocardpan"] = $pseudocardpan;
            if( $cc_3d_secure_enabled == "true")
            {
                $param_array["successurl"] = $success_url;
                $param_array["errorurl"] = $error_url;
            }

            //sort params in alphabetic order
            ksort($param_array);
            
            $parameter_string = "?";
            foreach( $param_array as $key => $parameter )
            {
                $parameter_string .= $key . "=". $parameter . "&";
            }
            
            $url = "https://secure.pay1.de/client-api" . $parameter_string;
            
            if ( $siteINI->hasVariable( 'ProxySettings', 'ProxyServer' ) && $siteINI->variable( 'ProxySettings', 'ProxyServer' ) != "" )
            {
                $proxyserver = $siteINI->variable( 'ProxySettings', 'ProxyServer' );
                //now get the proxy url
                if ( strpos($proxyserver, "://")  !== false)
                {
                    $proxy_parts = explode("://", $proxyserver);
                    $proxyserver = $proxy_parts[1];
                }

                $context_array = array('http'=>array('method' => 'GET', 'proxy' => $proxyserver));
                $context = stream_context_create($context_array);

                $json_response = file_get_contents($url, false, $context);
            }
            else
            {
                $json_response = file_get_contents($url);
            }

            if( $json_response )
            {
                $json_response = json_decode($json_response);
                if( $json_response->status != "ERROR" AND isset($json_response->txid) )
                {
                    //get 'txid' from response and keep it
                    $txid = $json_response->txid;
                    
                    //get 'userid' from response and keep it
                    $userid = $json_response->userid;
                    
                    //now store it into the order
                    $db = eZDB::instance();
                    $db->begin();
                    $doc = new DOMDocument( '1.0', 'utf-8' );
                    $doc->loadXML($order->DataText1);
                    $shop_account_element = $doc->getElementsByTagName('shop_account');
                    $shop_account_element = $shop_account_element->item(0);

                    //handle and store the TXID
                    //remove first if exists
                    $txid_elements = $doc->getElementsByTagName('txid');
                    if( $txid_elements->length >= 1 )
                    {
                        $txid_element = $txid_elements->item(0);
                        $txid_element->parentNode->removeChild($txid_element);
                    }
                    //then create
                    $txidNode = $doc->createElement( "txid", $txid );
                    $shop_account_element->appendChild( $txidNode );

                    //handle and store the userid
                    //remove first if exists
                    $userid_elements = $doc->getElementsByTagName('userid');
                    if( $userid_elements->length >= 1 )
                    {
                        $userid_element = $userid_elements->item(0);
                        $userid_element->parentNode->removeChild($userid_element);
                    }
                    //then create
                    $useridNode = $doc->createElement( "userid", $userid );
                    $shop_account_element->appendChild( $useridNode );

                    //handle and store the pseudocardpan
                    if ( $http->hasPostVariable( 'truncatedcardpan' ) )
                    {
                        //remove first if exists
                        $tpan_elements = $doc->getElementsByTagName('truncatedcardpan');
                        if( $tpan_elements->length >= 1 )
                        {
                            $tpan_element = $tpan_elements->item(0);
                            $tpan_element->parentNode->removeChild($tpan_element);
                        }
                        //then create
                        $truncatedcardpan_node = $doc->createElement( "truncatedcardpan", $http->postVariable( 'truncatedcardpan' ) );
                        $shop_account_element->appendChild( $truncatedcardpan_node );
                    }
                    if( $json_response->status === "REDIRECT" )
                    {
                        //remove first if exists
                        $cc3d_sec_elements = $doc->getElementsByTagName('cc3d_reserved');
                        if( $cc3d_sec_elements->length >= 1 )
                        {
                            $cc3d_sec_element = $cc3d_sec_elements->item(0);
                            $cc3d_sec_element->parentNode->removeChild($cc3d_sec_element);
                        }
                        //save reserved flag false for now
                        $reservedFlag = $doc->createElement( "cc3d_reserved", "false" );
                        $shop_account_element->appendChild( $reservedFlag );
                    }
                    else
                    {
                        //remove cc3d_reserved if exists. this case could occure if someone changed from 3d CC to normal CC.
                        $cc3d_sec_elements = $doc->getElementsByTagName('cc3d_reserved');
                        if( $cc3d_sec_elements->length >= 1 )
                        {
                            $cc3d_sec_element = $cc3d_sec_elements->item(0);
                            $cc3d_sec_element->parentNode->removeChild($cc3d_sec_element);
                        }
                    }

                    //i must store here redundant otherwise the order will not be stored since its stuck in a transaction
                    $db->commit();

                    //store it
                    $order->setAttribute( 'data_text_1', $doc->saveXML() );
                    $order->store();
                    $db->commit();

                    if( $json_response->status === "REDIRECT" )
                    {
                        eZLog::write("PENDING in step 2 ('preauthorisation') ::3D Secure Card detected - REDIRECTING to creditcard institute check :: for order ID " . $order_id, $logName = 'xrowpayone.log', $dir = 'var/log');
                        //do redirect to 3d secure password confirm page
                        http_redirect( $json_response->redirecturl );
                        exit;
                    }
                    else
                    {
                        xrowPayoneCreditCardGateway::setPaymentMethod($order);
                        eZLog::write("SUCCESS in step 2 ('preauthorisation') for order ID " . $order_id, $logName = 'xrowpayone.log', $dir = 'var/log');
                        return eZWorkflowType::STATUS_ACCEPTED;
                    }
                }
                else
                {
                    eZLog::write("FAILED in step 2 ('preauthorisation') for order ID " . $order_id . " with ERRORCODE " . $json_response->errorcode . " Message: " . $json_response->errormessage, $logName = 'xrowpayone.log', $dir = 'var/log');

                    if ($payoneINI->variable( 'GeneralSettings', 'CustomErrorNode' ) === "disabled")
                    {
                        //use default error of payone
                        $errors = array($json_response->customermessage);
                    }
                    else
                    {
                        //use customized errors
                        $response["errorcode"] = $json_response->errorcode;
                        $response["errormessage"] = $json_response->errormessage;
                        $errors = array( xrowPayoneHelper::generateCustomErrorString( $order, $response ) ) ;
                    }
                }
            }
            else
            {
                eZLog::write("ERROR: Remote content not found in file " . __FILE__ . " on line " . __LINE__, $logName = 'xrowpayone.log', $dir = 'var/log');
            }
        }
        else if ( is_object( $paymentObj ) )
        {
            //that means, that we have a paymentobject which is not approved. its not approved because the payment has failed so we return a array
            $errors = array( ezpI18n::tr( 'extension/xrowpayone', 'Error occured during payment process. Please choose your payment option again.') );
            $paymentObj->remove();
        }
        
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

    function setPaymentMethod( $order )
    {
        $db = eZDB::instance();
        $db->begin();

        $doc = new DOMDocument( '1.0', 'utf-8' );
        $doc->loadXML($order->DataText1);
        $shop_account_element = $doc->getElementsByTagName('shop_account');
        $shop_account_element = $shop_account_element->item(0);
        $paymentmethod = $doc->createElement( xrowECommerce::ACCOUNT_KEY_PAYMENTMETHOD, xrowPayoneCreditCardGateway::GATEWAY_STRING );
        $shop_account_element->appendChild( $paymentmethod );

        $db->commit();

        //store it
        $order->setAttribute( 'data_text_1', $doc->saveXML() );
        $order->store();
        $db->commit();
    }
}

xrowEPayment::registerGateway( xrowPayoneCreditCardGateway::GATEWAY_STRING, "xrowpayonecreditcardgateway", ezpI18n::tr( "extension/xrowpayone", "Credit Card" ) );

?>