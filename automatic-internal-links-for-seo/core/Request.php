<?php
namespace Pagup\AutoLinks\Core;

class Request
{
    /**
     * Get sanitized value if post request is set & value exists inside $safe array
     * 
     * @param string $key
     * @param array $safe
     * @return string|bool
    */
    public static function safe(string $key, array $safe)
    {

        if ( isset( $_POST[$key] ) && in_array( $_POST[$key], $safe ) ) 
        { 
            $request = sanitize_text_field( $_POST[$key] ); 
        }
        
        return $request ?? false;
    }


    public static function bool(string $key): int
    {

        if ( isset( $_POST[$key] ) ) 
        {  
            $request = sanitize_text_field( boolval($_POST[$key]) ) ? 1 : 0; 
        }
        
        return $request;
    }

    public static function check($key)
    {

        return isset( $_POST[$key] ) && !empty( $_POST[$key] ); 
        
    }

    /**
     * Sanitize each value inside array
     * 
     * @param array $array
     * @return array
    */
    public static function array( $array ): array {
        foreach ( (array) $array as $k => $v ) {
           if ( is_array( $v ) ) {
               $array[$k] =  array( $v );
           } else {
               $array[$k] = sanitize_text_field( $v );
           }
        }
     
       return $array;                                                       
    }
}