<?php


declare( strict_types = 1 );


namespace JDWX\Socket\Tests\Shims;


use JDWX\Socket\Socket;
use JDWX\Socket\SocketInterface;


class MockErrorSocket extends Socket {


    public static int $uLastError = 0;


    public static function clearError( \Socket|SocketInterface|null $i_socket = null ) : void {
        self::$uLastError = 0;
    }


    public static function lastError( \Socket|SocketInterface|null $i_socket = null ) : int {
        return self::$uLastError;
    }


    public static function setLastError( int $i_error ) : void {
        self::$uLastError = $i_error;
    }


}
