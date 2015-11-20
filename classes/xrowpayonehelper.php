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
}

?>