<?php


declare( strict_types = 1 );


use JDWX\Socket\Socket as JSocket;


require __DIR__ . '/../vendor/autoload.php';


( function () : void {

    $socketServer = JSocket::create( AF_INET, SOCK_STREAM, SOL_TCP );
    $socketServer->setOption( SOL_SOCKET, SO_REUSEADDR, 1 );
    $socketServer->bind( '127.0.0.1' );
    $socketServer->listen();
    $socketServer->getSockName( $stAddr, $uPort );
    /** @phpstan-ignore-next-line */
    assert( is_string( $stAddr ) );
    assert( is_int( $uPort ) );

    $socketClient = JSocket::create( AF_INET, SOCK_STREAM, SOL_TCP );
    $socketClient->connect( $stAddr, $uPort );

    $socket = $socketServer->accept();
    $socket->shutdown( 1 );

    $socketClient->send( 'This is normal data.' );
    $socketClient->send( '!', i_uFlags: MSG_OOB ); # TCP only allows one byte of urgent data.
    $socketClient->send( 'Not so urgent.' );
    $socketClient->shutdown();

    do {
        if ( $socket->atMark() ) {
            $rc = $socket->recv( $st, 65536, MSG_OOB );
            echo "Received urgent data: ({$rc}) {$st}\n";
        } else {
            $rc = $socket->recv( $st, 65536 );
            echo "Received normal data: ({$rc}) {$st}\n";
        }
    } while ( $rc > 0 );

} )();