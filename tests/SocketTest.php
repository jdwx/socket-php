<?php


declare( strict_types = 1 );


namespace JDWX\Socket\Tests;


use JDWX\Socket\Exceptions\ConnectionException;
use JDWX\Socket\Exceptions\Exception;
use JDWX\Socket\Socket as JSocket;
use JDWX\Socket\SocketInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( JSocket::class )]
final class SocketTest extends TestCase {


    public function testAccept() : void {
        [ $accepted, $client ] = $this->createInetPair();

        self::assertSame( $client->localPort(), $accepted->remotePort() );
        self::expectException( Exception::class );

        $client->accept();
    }


    public function testAtMark() : void {
        [ $accepted, $client ] = $this->createInetPair();
        self::assertFalse( $accepted->atMark() );
        $client->send( 'Hello' );
        $client->send( '!', i_uFlags: MSG_OOB );
        $client->send( 'Hello' );
        $accepted->recv( $st, 100 );
        usleep( 10_000 );
        # Regrettably, this test is somewhat unreliable.
        self::assertTrue( $accepted->atMark() );
        $accepted->recv( $st, 100, i_uFlags: MSG_OOB );
    }


    public function testBindAndListen() : void {
        $socket = JSocket::create( AF_INET, SOCK_STREAM, SOL_TCP );
        $socket->bind( '127.0.0.1' );
        $socket->listen();
        self::assertSame( '127.0.0.1', $socket->localAddress() );
        self::assertGreaterThan( 0, $socket->localPort() );
    }


    public function testBindForError() : void {
        $socket = JSocket::create( AF_INET, SOCK_STREAM, SOL_TCP );
        self::expectException( Exception::class );
        $socket->bind( '256.256.256.256' ); // Invalid address
    }


    public function testConnectSendReceive() : void {
        $server = JSocket::create( AF_INET, SOCK_STREAM, SOL_TCP );
        $server->bind( '127.0.0.1' );
        $server->listen();
        $port = $server->localPort();

        $client = JSocket::create( AF_INET, SOCK_STREAM, SOL_TCP );
        $client->connect( '127.0.0.1', $port );

        $accepted = $server->accept();

        $msg = 'Hello';
        $client->write( $msg );
        $received = $accepted->read( strlen( $msg ) );
        self::assertSame( $msg, $received );

        $client->shutdown();
    }


    public function testCreate() : void {
        $socket = JSocket::create( AF_INET, SOCK_STREAM, SOL_TCP );
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        self::assertInstanceOf( JSocket::class, $socket );

        self::expectException( Exception::class );
        JSocket::create( AF_INET, SOCK_STREAM, 9999 );
    }


    public function testCreateBoundForTcp() : void {
        $sock = JSocket::createBound( '127.0.0.1' );

        $sock2 = JSocket::create( AF_INET, SOCK_STREAM, SOL_TCP );
        $sock2->connect( '127.0.0.1', $sock->localPort() );

        $sock2->send( 'Hello' );
        $sock3 = $sock->accept();
        self::assertSame( 'Hello', $sock3->read( 1024 ) );
    }


    public function testCreateBoundForUdp() : void {
        $sock = JSocket::createBound( '127.0.0.1', 0, SOCK_DGRAM, SOL_UDP );
        $sock2 = JSocket::create( AF_INET, SOCK_DGRAM, SOL_UDP );
        $sock2->sendTo( 'Hello', null, '127.0.0.1', $sock->localPort() );
        self::assertSame( 5, $sock->recv( $st, 1024 ) );
        self::assertSame( 'Hello', $st );
    }


    public function testCreateByAddressForIPv4() : void {
        $sockFar = JSocket::create( AF_INET, SOCK_STREAM, SOL_TCP );
        $sockFar->bind( '127.0.0.1' );
        $sockFar->listen();
        $uPort = $sockFar->localPort();

        $sock = JSocket::createByAddress( '127.0.0.1', SOCK_STREAM, SOL_TCP );
        $sock->connect( '127.0.0.1', $uPort );
        $sock->send( 'Hello' );

        $sock2 = $sockFar->accept();

        self::assertSame( 'Hello', $sock2->read( 1024 ) );
    }


    public function testCreateByAddressForIPv6() : void {
        $sockFar = JSocket::create( AF_INET6, SOCK_STREAM, SOL_TCP );
        $sockFar->bind( '::1' );
        $sockFar->listen();
        $uPort = $sockFar->localPort();

        $sock = JSocket::createByAddress( '::1', SOCK_STREAM, SOL_TCP );
        $sock->connect( '::1', $uPort );
        $sock->send( 'Hello' );

        $sock2 = $sockFar->accept();

        self::assertSame( 'Hello', $sock2->read( 1024 ) );
    }


    public function testCreateByAddressForUnix() : void {
        $sockFar = JSocket::create( AF_UNIX, SOCK_STREAM );
        $stSocketPath = sys_get_temp_dir() . '/sock-test.' . random_int( 1_000_000, 9_999_999 ) . '.sock';
        self::assertFileDoesNotExist( $stSocketPath, 'Socket path should not exist before binding' );
        $sockFar->bind( $stSocketPath );
        $sockFar->listen();

        $sock = JSocket::createByAddress( $stSocketPath, SOCK_STREAM );
        $sock->connect( $stSocketPath );
        $sock->send( 'Hello' );

        $sock2 = $sockFar->accept();

        self::assertSame( 'Hello', $sock2->read( 1024 ) );

        unlink( $stSocketPath );
    }


    public function testCreateListen() : void {
        $socket = JSocket::createListen( 0 );
        $socket->getSockName( $stAddress, $uPort );
        assert( is_int( $uPort ) );
        self::assertSame( '0.0.0.0', $stAddress );
        self::assertGreaterThan( 0, $uPort );

        self::expectException( Exception::class );
        JSocket::createListen( $uPort );
    }


    public function testCreatePair() : void {
        $pair = JSocket::createPair();
        self::assertCount( 2, $pair );
        [ $sock1, $sock2 ] = $pair;
        $msg = 'Ping';
        $sock1->write( $msg );
        $received = $sock2->read( strlen( $msg ) );
        self::assertSame( $msg, $received );

        self::expectException( Exception::class );
        JSocket::createPair( AF_UNIX, SOCK_DGRAM, 9999 );
    }


    public function testExceptionOnInvalidConnect() : void {
        $socket = JSocket::create( AF_INET, SOCK_STREAM, SOL_TCP );
        $this->expectException( ConnectionException::class );
        $socket->connect( '256.256.256.256', 12345 );
    }


    public function testGetPeerNameForError() : void {
        $socket = JSocket::create( AF_INET, SOCK_STREAM, SOL_TCP );
        self::expectException( Exception::class );
        $socket->getPeerName( $stAddress, $uPort );
    }


    public function testImportStream() : void {
        $stream = fsockopen( 'udp://127.0.0.1', 12345 );
        assert( is_resource( $stream ), 'Failed to create stream' );
        $socket = JSocket::importStream( $stream );
        self::assertSame( 12345, $socket->remotePort() );
    }


    public function testLastError() : void {
        JSocket::clearError();
        self::assertSame( 0, JSocket::lastError() );

        $socket = socket_create( AF_INET, SOCK_STREAM, SOL_TCP );
        assert( $socket instanceof \Socket );
        JSocket::clearError( $socket );
        self::assertSame( 0, JSocket::lastError( $socket ) );

        $socket = JSocket::create( AF_INET, SOCK_STREAM, SOL_TCP );
        JSocket::clearError( $socket );
        self::assertSame( 0, JSocket::lastError( $socket ) );
    }


    public function testReadTimed() : void {
        [ $sock1, $sock2 ] = JSocket::createPair();
        $sock1->send( 'Hello' );
        $b = null;
        self::assertSame( 'Hello', $sock2->readTimed( 5, 1, o_bComplete: $b ) );
        /** @noinspection PhpUnitAssertTrueWithIncompatibleTypeArgumentInspection */
        self::assertTrue( $b );
        $sock1->send( 'World' );
        self::assertSame( 'World', $sock2->readTimed( 10, 0,
            10_000, o_bComplete: $b ) );
        self::assertFalse( $b );
    }


    public function testRecvMsgForIOVec() : void {
        [ $sock1, $sock2 ] = JSocket::createPair();
        $sock1->send( 'FooBarBaz' );
        $r = [
            'name' => [],
            'buffer_size' => 1024,
            'controllen' => socket_cmsg_space( IPPROTO_IPV6, IPV6_PKTINFO ) +
                socket_cmsg_space( IPPROTO_IPV6, IPV6_TCLASS ),
        ];
        self::assertSame( 9, $sock2->recvMsg( $r ) );
        assert( is_array( $r[ 'iov' ] ) );
        self::assertSame( 'FooBarBaz', $r[ 'iov' ][ 0 ] );
    }


    public function testRecvTimed() : void {
        [ $sock1, $sock2 ] = JSocket::createPair();
        $sock1->send( 'Hello' );
        $st = '';
        self::assertSame( 5, $sock2->recvTimed( $st, 5 ) );
        self::assertSame( 'Hello', $st );
        /** @noinspection PhpUnitAssertTrueWithIncompatibleTypeArgumentInspection */
        $sock1->send( 'World' );
        self::assertSame( 5, $sock2->recvTimed( $st, 10 ) );
        self::assertSame( 'World', $st );
    }


    public function testRemoteName() : void {
        [ $accepted, $client ] = $this->createInetPair();
        self::assertSame( $accepted->remoteAddress(), $client->localAddress() );
        self::assertSame( $accepted->remotePort(), $client->localPort() );
    }


    public function testSelect() : void {
        [ $sock1, $sock2 ] = JSocket::createPair();

        // Write data to sock1 so sock2 becomes readable
        $message = 'test';
        $sock1->write( $message );

        $read = [ $sock2 ];
        $write = [ $sock1 ];
        $except = null;

        $numReady = JSocket::select( $read, $write, $except, 1 );
        assert( is_array( $read ) );
        assert( is_array( $write ) );

        self::assertGreaterThanOrEqual( 1, $numReady, 'At least one socket should be ready' );
        self::assertContains( $sock2, $read, 'sock2 should be ready for reading' );
        self::assertContains( $sock1, $write, 'sock1 should be ready for writing' );
        self::assertNull( $except, 'No sockets should be exceptional' );

        // Test with no sockets ready (after reading all data)
        $sock2->read( strlen( $message ) );
        $read = [ $sock2 ];
        $write = null;
        $except = null;
        $numReady = JSocket::select( $read, $write, $except, 0, 100000 ); // 0.1s

        self::assertSame( 0, $numReady, 'No sockets should be ready after reading all data' );
    }


    public function testSelectForRead() : void {
        [ $sock1, $sock2 ] = JSocket::createPair();
        self::assertFalse( $sock1->selectForRead() );
        $sock2->write( 'Hello' );
        self::assertTrue( $sock1->selectForRead( 1 ) ); // Wait for 1 second (but it won't)
    }


    public function testSelectForWrite() : void {
        [ $sock1, $sock2 ] = JSocket::createPair();
        self::assertTrue( $sock1->selectForWrite() );
        $sock1->setNonBlock();
        $st = str_repeat( 'Whatever', 8192 );
        self::assertNotEquals( strlen( $st ), $sock1->write( $st ) );
        self::assertFalse( $sock1->selectForWrite() );
        unset( $sock2 );
    }


    public function testSendMsgForIOVec() : void {
        [ $sock1, $sock2 ] = JSocket::createPair();
        $sock1->sendMsg( [
            'iov' => [ 'Foo', 'Bar', 'Baz' ],
        ] );
        self::assertSame( 'FooBarBaz', $sock2->read( 1024 ) );
    }


    /** @return list<SocketInterface> */
    private function createInetPair() : array {
        $server = JSocket::create( AF_INET, SOCK_STREAM, SOL_TCP );
        $server->setOption( SOL_SOCKET, SO_REUSEADDR, 1 );
        $server->bind( '127.0.0.1' );
        $server->listen();

        $client = JSocket::create( AF_INET, SOCK_STREAM, SOL_TCP );
        $client->connect( '127.0.0.1', $server->localPort() );

        $accepted = $server->accept();

        return [ $accepted, $client ];
    }


}
