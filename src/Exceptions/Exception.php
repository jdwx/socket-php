<?php


declare( strict_types = 1 );


namespace JDWX\Socket\Exceptions;


use JDWX\Socket\Socket;


class Exception extends \Exception {


    public function __construct( \Socket|Socket|null $i_socket = null, string $message = '', int $code = 0,
                                 ?\Throwable         $previous = null ) {
        $uLastError = Socket::lastError( $i_socket );
        if ( $uLastError !== 0 ) {
            $stLastError = Socket::strError( $uLastError );
            if ( empty( $message ) ) {
                $message = $stLastError;
            } else {
                $message .= " [{$stLastError}]";
            }
        }

        parent::__construct( $message, $code, $previous );
    }


}
