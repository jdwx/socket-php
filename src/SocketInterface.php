<?php


declare( strict_types = 1 );


namespace JDWX\Socket;


use JDWX\Socket\Exceptions\Exception;
use JDWX\Socket\Exceptions\ReadException;


interface SocketInterface {


    public static function addrInfoBind( \AddressInfo $i_address ) : static;


    public static function addrInfoConnect( \AddressInfo $i_address ) : static;


    /** @return mixed[] */
    public static function addrInfoExplain( \AddressInfo $i_address ) : array;


    /**
     * @param mixed[] $i_rHints
     * @return \AddressInfo[]
     */
    public static function addrInfoLookup( string $i_stHost, ?string $i_nstService = null, array $i_rHints = [] ) : array;


    public static function clearError( \Socket|SocketInterface|null $socket = null ) : void;


    public static function cmsgSpace( int $i_uLevel, int $i_uType, int $i_uNum = 0 ) : int;


    public static function create( int $i_uDomain, int $i_uType, int $i_uProtocol = 0 ) : static;


    public static function createListen( int $i_uPort, int $i_uBacklog = SOMAXCONN ) : static;


    /**
     * @param int $i_uDomain
     * @param int $i_uType
     * @param int $i_uProtocol
     * @return list<SocketInterface>
     * @throws Exception
     */
    public static function createPair( int $i_uDomain = AF_UNIX, int $i_uType = SOCK_STREAM,
                                       int $i_uProtocol = 0 ) : array;


    /** @param resource $i_stream */
    public static function importStream( $i_stream ) : static;


    /** @suppress PhanTypeMismatchArgumentNullableInternal Phan doesn't know socket_last_error() takes null since 8.0. */
    public static function lastError( \Socket|SocketInterface|null $socket = null ) : int;


    /**
     * @param list<\Socket|SocketInterface>|null $io_read
     * @param list<\Socket|SocketInterface>|null $io_write
     * @param list<\Socket|SocketInterface>|null $io_except
     * @param int $i_uSeconds
     * @param int $i_uMicroSeconds
     * @return int
     * @throws Exception
     */
    public static function select( ?array &$io_read = null, ?array &$io_write = null, ?array &$io_except = null,
                                   int    $i_uSeconds = 0, int $i_uMicroSeconds = 0 ) : int;


    public static function strError( int $i_uErrorCode ) : string;


    public function accept() : static;


    public function atMark() : bool;


    public function bind( string $i_address, int $i_port = 0 ) : void;


    /** @suppress PhanTypeMismatchArgumentNullableInternal Port nullable since 8.0. */
    public function connect( string $i_address, ?int $i_port = null ) : void;


    /**
     * @param string|null $o_stAddress
     * @param-out string $o_stAddress
     * @param int|null $o_nuPort
     * @return void
     * @throws Exception
     */
    public function getPeerName( ?string &$o_stAddress = null, ?int &$o_nuPort = null ) : void;


    /**
     * @param string|null $o_stAddress
     * @param-out string $o_stAddress
     * @param int|null $o_uPort
     * @return void
     * @throws Exception
     */
    public function getSockName( ?string &$o_stAddress, ?int &$o_uPort ) : void;


    public function listen( int $i_uBacklog = 0 ) : void;


    public function localAddress() : string;


    public function localPort() : int;


    public function read( int $i_uLength, int $i_uMode = PHP_BINARY_READ ) : string;


    public function recv( ?string &$o_stData, int $i_uLength, int $i_uFlags = 0 ) : int;


    /**
     * @param string|null $o_stData
     * @param-out string $o_stData
     * @param int $i_uLength
     * @param int $i_uFlags
     * @param string $o_stAddress
     * @param int|null $o_nuPort
     * @return int
     * @throws ReadException
     */
    public function recvFrom( ?string &$o_stData, int $i_uLength, int $i_uFlags = 0, string &$o_stAddress = '',
                              ?int    &$o_nuPort = null ) : int;


    /**
     * @param mixed[] $o_rMessage
     * @param int $i_uFlags
     * @return int
     */
    public function recvMsg( array &$o_rMessage, int $i_uFlags = 0 ) : int;


    public function remoteAddress() : string;


    public function remotePort() : int;


    public function send( string $i_stData, ?int $i_nuLen = null, int $i_uFlags = 0 ) : int;


    /**
     * @param mixed[] $i_rMessage
     * @param int $i_uFlags
     * @return int
     */
    public function sendMsg( array $i_rMessage, int $i_uFlags = 0 ) : int;


    public function sendTo( string $i_stData, ?int $i_nuLen = null, string $i_stAddress = '', ?int $i_nuPort = null,
                            int    $i_uFlags = 0 ) : int;


    public function setBlock() : void;


    public function setNonBlock() : void;


    /**
     * @param int $i_uLevel
     * @param int $i_uOption
     * @param mixed[]|int|string $i_value
     * @return void
     * @throws Exception
     */
    public function setOption( int $i_uLevel, int $i_uOption, array|int|string $i_value ) : void;


    public function shutdown( int $i_uHow = 2 ) : void;


    public function socket() : \Socket;


    public function write( string $i_stData, ?int $i_nuLen = null ) : int;


    // socket_wsaprotocol_info... Sorry, I don't do Windows. (And hence can't implement these reliably.)


}