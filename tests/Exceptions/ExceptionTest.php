<?php


declare( strict_types = 1 );


namespace JDWX\Socket\Tests\Exceptions;


require_once __DIR__ . '/../Shims/MockErrorSocket.php';


use JDWX\Socket\Exceptions\Exception;
use JDWX\Socket\Socket;
use JDWX\Socket\Tests\Shims\MockErrorSocket;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( Exception::class )]
final class ExceptionTest extends TestCase {


    public function testConstructForError() : void {

        $mock = MockErrorSocket::create( AF_INET, SOCK_STREAM, SOL_TCP );
        MockErrorSocket::setLastError( 22 ); // Invalid argument error
        $ex = new Exception( $mock, 'Foo' );
        self::assertStringContainsString( 'Invalid argument', $ex->getMessage() );

        $ex = new Exception( $mock );
        self::assertSame( 'Invalid argument', $ex->getMessage() );

    }


    public function testConstructForNoError() : void {
        $message = 'Test exception';
        $code = 123;
        $previous = new \Exception( 'Previous exception' );
        $sock = Socket::create( AF_INET, SOCK_STREAM, SOL_TCP );

        $exception = new Exception( $sock, $message, $code, $previous );

        # Message did not change
        self::assertSame( $message, $exception->getMessage() );
        self::assertSame( $code, $exception->getCode() );
        self::assertSame( $previous, $exception->getPrevious() );
    }


    public function testConstructForNoSocket() : void {
        $ex = new Exception( null, 'Test exception' );
        self::assertSame( 'Test exception', $ex->getMessage() );
    }


}
