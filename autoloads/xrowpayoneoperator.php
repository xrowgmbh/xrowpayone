<?php

class xrowpayoneOperators
{
    function xrowpayoneOperators()
    {
        $this->Operators = array( 'hashcreate', 'payone_info_by_order' );
    }

    function operatorList()
    {
        return array( 'hashcreate', 'payone_info_by_order' );
    }

    function namedParameterPerOperator()
    {
        return true;
    }

    function namedParameterList()
    {
        return array( 'hashcreate' => array ( 'algorithm'  => array( "type" => "string",
                                                                     "required" => true),
                                              'hash_array'  => array( "type" => "array",
                                                                      "required" => true),
                                              'key'  => array( "type" => "string",
                                                               "required" => true)),
                      'payone_info_by_order' => array ( 'order'  => array( "type" => "object",
                                                                           "required" => true))

                    );
    }

    function modify( $tpl, $operatorName, $operatorParameters, $rootNamespace, $currentNamespace, &$operatorValue, $namedParameters,  $placement)
    {
        switch ( $operatorName )
        {
            case 'hashcreate':
            {
                $algorithm = $namedParameters['algorithm'];
                $hash_array = $namedParameters['hash_array'];
                $key = $namedParameters['key'];

                $operatorValue = xrowPayoneHelper::generate_hash( $algorithm, $hash_array, $key );
            } break;
            case 'payone_info_by_order':
            {
                $order = $namedParameters['order'];
                $payone_info = array();

                if( $order instanceof eZOrder )
                {
                    $doc = new DOMDocument( '1.0', 'utf-8' );
                    $doc->loadXML($order->DataText1);

                    //try to fetch txid
                    $txid_element = $doc->getElementsByTagName('txid');
                    if( $txid_element->length >= 1 )
                    {
                        $payone_info["txid"] = (string)$txid_element->item(0)->nodeValue;
                    }

                    //try to fetch txid
                    $truncatedcardpan_element = $doc->getElementsByTagName('truncatedcardpan');
                    if( $txid_element->length >= 1 )
                    {
                        $payone_info["truncatedcardpan"] = (string)$truncatedcardpan_element->item(0)->nodeValue;
                    }

                    //try to fetch userid
                    $userid_element = $doc->getElementsByTagName('userid');
                    if( $userid_element->length >= 1 )
                    {
                        $payone_info["userid"] = (string)$userid_element->item(0)->nodeValue;
                    }
                    
                    //try to fetch 3d secure payment status
                    $cc3d_reserved_element = $doc->getElementsByTagName('cc3d_reserved');
                    if( $cc3d_reserved_element->length >= 1 )
                    {
                        $payone_info["cc3d_reserved"] = (string)$cc3d_reserved_element->item(0)->nodeValue;
                    }
                }
                else
                {
                    eZLog::write("\$order is not an instance of eZOrder in extension/xrowpayone/autoloads/xrowpayoneoperator.php", $logName = 'xrowpayone.log', $dir = 'var/log');
                }

                if ( count($payone_info) == 0 )
                {
                    $operatorValue = false;
                }
                else
                {
                    $operatorValue = $payone_info;
                }
            } break;
        }
    }
}

?>