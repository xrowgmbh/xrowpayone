<?php

class xrowPayoneHelper
{
    public function generate_hash( $algorithm, $hash_array, $key )
    {
        ksort($hash_array);
        $hash_text = "";

        foreach ( $hash_array as $hash )
        {
            $hash_text .= str_replace(' ', '', $hash);
        }

        return strtolower( hash_hmac($algorithm, $hash_text, $key) );
    }

    public function generateCustomErrorString( $order, $response )
    {
        $payoneINI = eZINI::instance( 'xrowpayone.ini' );
        $custom_error_node_id = $payoneINI->variable( 'GeneralSettings', 'CustomErrorNode' );
        $error_fallback = $payoneINI->variable( 'GeneralSettings', 'CustomErrorFallback' );
        $error_fallback = ezpI18n::tr( 'extension/xrowpayone', $error_fallback );

        if ($custom_error_node_id !== "disabled")
        {
            if ( is_numeric($custom_error_node_id) )
            {
                $custom_error_node = eZContentObjectTreeNode::fetch( $custom_error_node_id );
                if ( isset($custom_error_node) AND $custom_error_node instanceof eZContentObjectTreeNode )
                {
                    $error_code = $response["errorcode"];
                    $data_map = $custom_error_node->dataMap();
                    $matrix_identifier = $payoneINI->variable( 'GeneralSettings', 'CustomErrorNodeMatrixIdentifier' );
                    if ( isset($data_map[$matrix_identifier]) )
                    {
                        $matrix_attribute_content = $data_map[$matrix_identifier]->content();
                        $matrix_data = $matrix_attribute_content->Matrix;
                        $matrix_rows = $matrix_data["rows"];
                        $matrix_rows = $matrix_rows["sequential"];
                        
                        foreach ( $matrix_rows as $row )
                        {
                            //now map the error code to the matrix code
                            if ( $row["columns"]["0"] == $error_code )
                            {
                                $custom_errormessage = $row["columns"]["1"];
                                //return the translated code from object
                                return $custom_errormessage;
                            }
                        }
                    }
                    else
                    {
                        eZLog::write("No attribute identifier named ". $matrix_identifier . " found. Please check your configuration 'xrowpayone.ini', GeneralSettings', 'CustomErrorNode'", $logName = 'xrowpayone.log', $dir = 'var/log');
                    }
                }
                else
                {
                    eZLog::write("Could not fetch node from settings 'xrowpayone.ini', GeneralSettings', 'CustomErrorNode' please check your configuration", $logName = 'xrowpayone.log', $dir = 'var/log');
                }
            }
        }
        //the worst fallback
        return $error_fallback;
    }
}

?>