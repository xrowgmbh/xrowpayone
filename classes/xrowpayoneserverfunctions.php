<?php

class xrowPayoneServerFunctions extends ezjscServerFunctions
{
    public static function custom_error_handling()
    {
        $http = eZHTTPTool::instance();
        $order_id = $http->postVariable( 'order_id' );
        $order = eZOrder::fetch( $order_id );
        $response = $http->postVariable( 'response' );

        //write error to log file
        self::write_invalid_checkcreditcard_log($order_id, $response);

        //generate the string and return it back to the template full translated
        return xrowPayoneHelper::generateCustomErrorString( $order, $response );
    }

    public static function write_valid_checkcreditcard_log( $order_id )
    {
        //set fallbacks for direct ajax request
        if( !isset($order_id) OR ( is_array($order_id) AND count($order_id) == 0 ) )
        {
            $http = eZHTTPTool::instance();
            $order_id = $http->postVariable( 'order_id' );
        }
        eZLog::write("SUCCESS in step 1 ('checkcreditcard') for order ID " . $order_id, $logName = 'xrowpayone.log', $dir = 'var/log');
    }

    public static function write_invalid_checkcreditcard_log($order_id, $response = array("errorcode" => "unknown", "errormessage" => "unknown") )
    {
        $http = eZHTTPTool::instance();
        //set fallbacks for direct ajax request
        if( !isset($order_id) OR ( is_array($order_id) AND count($order_id) == 0 ) )
        {
            $order_id = $http->postVariable( 'order_id' );
        }
        
        if( !isset($response) OR ( is_array($response) AND count($response) == 0 ) )
        {
            $response = $http->postVariable( 'response' );
        }

        eZLog::write("FAILED in step 1 ('checkcreditcard') for order ID " . $order_id . " with ERRORCODE " . $response["errorcode"] . " Message: " . $response["errormessage"], $logName = 'xrowpayone.log', $dir = 'var/log');
    }
}

?>