<?php


declare( strict_types = 1 );


namespace JDWX\Socket\Exceptions;


use JDWX\Socket\Socket;
use JDWX\Socket\SocketInterface;


class Exception extends \Exception {


    public function __construct( \Socket|SocketInterface|null $i_socket = null, string $message = '', int $code = 0,
                                 ?\Throwable                  $previous = null ) {
        if ( $i_socket instanceof SocketInterface ) {
            $uLastError = $i_socket::lastError();
        } else {
            $uLastError = Socket::lastError( $i_socket );
        }
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
