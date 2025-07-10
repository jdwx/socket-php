<?php /** @noinspection PhpUsageOfSilenceOperatorInspection */


declare( strict_types = 1 );


namespace JDWX\Socket;


use JDWX\Socket\Exceptions\ConnectionException;
use JDWX\Socket\Exceptions\Exception;
use JDWX\Socket\Exceptions\ReadException;
use JDWX\Socket\Exceptions\WriteException;
use LogicException;


class Socket implements SocketInterface {


    final protected function __construct( protected \Socket $socket ) {}


    public static function addrInfoBind( \AddressInfo $i_address ) : static {
        $sock = @socket_addrinfo_bind( $i_address );
        if ( $sock instanceof \Socket ) {
            return new static( $sock );
        }
        $st = serialize( socket_addrinfo_explain( $i_address ) );
        throw new Exception( null, "Socket::addrInfoBind( {$st} ) failed" );
    }


    public static function addrInfoConnect( \AddressInfo $i_address ) : static {
        $sock = @socket_addrinfo_connect( $i_address );
        if ( $sock instanceof \Socket ) {
            return new static( $sock );
        }
        $st = serialize( self::addrInfoExplain( $i_address ) );
        throw new Exception( null, "Socket::addrInfoConnect( {$st} ) failed" );
    }


    /** @return mixed[] */
    public static function addrInfoExplain( \AddressInfo $i_address ) : array {
        return socket_addrinfo_explain( $i_address );
    }


    /**
     * @param mixed[] $i_rHints
     * @return \AddressInfo[]
     */
    public static function addrInfoLookup( string $i_stHost, ?string $i_nstService = null, array $i_rHints = [] ) : array {
        $aInfo = @socket_addrinfo_lookup( $i_stHost, $i_nstService, $i_rHints );
        if ( is_array( $aInfo ) ) {
            return $aInfo;
        }
        $stHints = serialize( $i_rHints );
        throw new Exception( null, "Socket::addrInfoLookup( {$i_stHost}, {$i_nstService}, {$stHints} ) failed" );
    }


    /** @suppress PhanTypeMismatchArgumentNullableInternal Phan doesn't know socket_clear_error() takes null since 8.0. */
    public static function clearError( \Socket|SocketInterface|null $socket = null ) : void {
        if ( $socket instanceof self ) {
            $socket = $socket->socket();
        }
        assert( $socket instanceof \Socket || $socket === null );
        socket_clear_error( $socket );
    }


    public static function cmsgSpace( int $i_uLevel, int $i_uType, int $i_uNum = 0 ) : int {
        $nu = @socket_cmsg_space( $i_uLevel, $i_uType, $i_uNum );
        if ( is_int( $nu ) ) {
            return $nu;
        }
        throw new Exception( null, "Socket::cmsgSpace( {$i_uLevel}, {$i_uType}, {$i_uNum} ) failed" );
    }


    public static function create( int $i_uDomain, int $i_uType, int $i_uProtocol = 0 ) : static {
        $sock = @socket_create( $i_uDomain, $i_uType, $i_uProtocol );
        if ( $sock instanceof \Socket ) {
            return new static( $sock );
        }
        throw new Exception( null, "Socket::create( {$i_uDomain}, {$i_uType}, {$i_uProtocol} ) failed" );
    }


    public static function createBound( string $i_stAddress, int $i_uPort = 0, int $i_uType = SOCK_STREAM,
                                        int    $i_uProtocol = 0 ) : static {
        $sock = self::createByAddress( $i_stAddress, $i_uType, $i_uProtocol );
        $sock->bind( $i_stAddress, $i_uPort );
        if ( $i_uType === SOCK_STREAM ) {
            $sock->listen();
        }
        return $sock;
    }


    public static function createByAddress( string $i_stAddress, int $i_uType, int $i_uProtocol = 0 ) : static {
        $uDomain = AF_INET;
        if ( str_contains( $i_stAddress, ':' ) ) {
            $uDomain = AF_INET6;
        } elseif ( str_starts_with( $i_stAddress, '/' ) ) {
            $uDomain = AF_UNIX;
        }
        return self::create( $uDomain, $i_uType, $i_uProtocol );
    }


    public static function createListen( int $i_uPort, int $i_uBacklog = SOMAXCONN ) : static {
        $sock = @socket_create_listen( $i_uPort, $i_uBacklog );
        if ( $sock instanceof \Socket ) {
            return new static( $sock );
        }
        throw new Exception( null, "Socket::createListen( {$i_uPort}, {$i_uBacklog} ) failed" );
    }


    /**
     * @param int $i_uDomain
     * @param int $i_uType
     * @param int $i_uProtocol
     * @return list<Socket>
     * @throws Exception
     */
    public static function createPair( int $i_uDomain = AF_UNIX, int $i_uType = SOCK_STREAM,
                                       int $i_uProtocol = 0 ) : array {
        if ( true === @socket_create_pair( $i_uDomain, $i_uType, $i_uProtocol, $pair ) ) {
            assert( is_array( $pair ) );
            assert( $pair[ 0 ] instanceof \Socket );
            assert( $pair[ 1 ] instanceof \Socket );
            return [ new static( $pair[ 0 ] ), new static( $pair[ 1 ] ) ];
        }
        throw new Exception( null, "Socket::createPair( {$i_uDomain}, {$i_uType}, {$i_uProtocol} ) failed" );
    }


    /** @param resource $i_stream */
    public static function importStream( $i_stream ) : static {
        $sock = @socket_import_stream( $i_stream );
        if ( $sock instanceof \Socket ) {
            return new static( $sock );
        }
        throw new Exception( null, 'Socket::importStream() failed' );
    }


    /** @suppress PhanTypeMismatchArgumentNullableInternal Phan doesn't know socket_last_error() takes null since 8.0. */
    public static function lastError( \Socket|SocketInterface|null $socket = null ) : int {
        if ( $socket instanceof self ) {
            $socket = $socket->socket();
        }
        assert( $socket instanceof \Socket || $socket === null );
        return socket_last_error( $socket );
    }


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
                                   int    $i_uSeconds = 0, int $i_uMicroSeconds = 0 ) : int {
        $read = [];
        foreach ( $io_read ?? [] as $sock ) {
            if ( $sock instanceof self ) {
                $sock = $sock->socket();
            }
            assert( $sock instanceof \Socket );
            $read[] = $sock;
        }

        $write = [];
        foreach ( $io_write ?? [] as $sock ) {
            if ( $sock instanceof self ) {
                $sock = $sock->socket();
            }
            assert( $sock instanceof \Socket );
            $write[] = $sock;
        }

        $except = [];
        foreach ( $io_except ?? [] as $sock ) {
            if ( $sock instanceof self ) {
                $sock = $sock->socket();
            }
            assert( $sock instanceof \Socket );
            $except[] = $sock;
        }

        $rc = @socket_select( $read, $write, $except, $i_uSeconds, $i_uMicroSeconds );
        if ( ! is_int( $rc ) ) {
            $stRead = count( $read );
            $stWrite = count( $write );
            $stExcept = count( $except );
            throw new Exception( null, "Socket::select( {$stRead}, {$stWrite}, {$stExcept}, {$i_uSeconds}, {$i_uMicroSeconds} ) failed" );
        }

        $io_read = self::processArray( $io_read, $read );
        $io_write = self::processArray( $io_write, $write );
        $io_except = self::processArray( $io_except, $except );
        return $rc;

    }


    public static function strError( int $i_uErrorCode ) : string {
        return socket_strerror( $i_uErrorCode );
    }


    /**
     * @param list<\Socket|SocketInterface>|null $i_rInArray
     * @param array<\Socket> $i_rOutArray
     * @return list<\Socket|SocketInterface>|null
     */
    private static function processArray( ?array $i_rInArray, array $i_rOutArray ) : ?array {
        if ( ! is_array( $i_rInArray ) ) {
            return null;
        }
        $rOut = [];
        foreach ( $i_rOutArray as $outSock ) {
            foreach ( $i_rInArray as $inSock ) {
                if ( $outSock === $inSock ) {
                    $rOut[] = $inSock;
                    break;
                }
                if ( $inSock instanceof self && $outSock === $inSock->socket() ) {
                    $rOut[] = $inSock;
                    break;
                }
            }
        }
        return $rOut;
    }


    public function __destruct() {
        set_error_handler( null );
        @socket_close( $this->socket );
        restore_error_handler();
    }


    public function accept() : static {
        $sock = @socket_accept( $this->socket );
        if ( $sock instanceof \Socket ) {
            return new static( $sock );
        }
        throw new ConnectionException( $this->socket, 'Socket::accept() failed' );
    }


    public function atMark() : bool {
        return socket_atmark( $this->socket );
    }


    public function bind( string $i_address, int $i_port = 0 ) : void {
        if ( true === @socket_bind( $this->socket, $i_address, $i_port ) ) {
            return;
        }
        throw new ConnectionException( $this->socket, "Socket::bind( {$i_address}, {$i_port} ) failed" );
    }


    /** @suppress PhanTypeMismatchArgumentNullableInternal Port nullable since 8.0. */
    public function connect( string $i_address, ?int $i_port = null ) : void {
        if ( true === @socket_connect( $this->socket, $i_address, $i_port ) ) {
            return;
        }
        $i_port ??= '(null)';
        throw new ConnectionException( $this->socket, "Socket::connect( {$i_address}, {$i_port} ) failed" );
    }


    /**
     * @param string|null $o_stAddress
     * @param-out string $o_stAddress
     * @param int|null $o_nuPort
     * @return void
     * @throws Exception
     */
    public function getPeerName( ?string &$o_stAddress = null, ?int &$o_nuPort = null ) : void {
        $rc = @socket_getpeername( $this->socket, $o_stAddress, $o_nuPort );
        if ( true === $rc ) {
            return;
        }
        throw new Exception( $this->socket, "Socket::getPeerName( {$o_stAddress}, {$o_nuPort} ) failed" );
    }


    /**
     * @param string|null $o_stAddress
     * @param-out string $o_stAddress
     * @param int|null $o_uPort
     * @return void
     * @throws Exception
     */
    public function getSockName( ?string &$o_stAddress, ?int &$o_uPort ) : void {
        $rc = socket_getsockname( $this->socket, $o_stAddress, $o_uPort );
        if ( true === $rc ) {
            return;
        }
        throw new Exception( $this->socket, "Socket::getSockName({$o_stAddress}, {$o_uPort}) failed" );
    }


    public function listen( int $i_uBacklog = 0 ) : void {
        if ( true === @socket_listen( $this->socket, $i_uBacklog ) ) {
            return;
        }
        throw new Exception( $this->socket, "Socket::listen( {$i_uBacklog} ) failed" );
    }


    public function localAddress() : string {
        $this->getSockName( $stAddress, $uPort );
        /** @phpstan-ignore-next-line */
        assert( is_string( $stAddress ) );
        return $stAddress;
    }


    public function localPort() : int {
        $this->getSockName( $stAddress, $nuPort );
        if ( is_int( $nuPort ) ) {
            return $nuPort;
        }
        throw new LogicException( 'Asked for local port on family that does not use ports.' );
    }


    public function read( int $i_uMaxLength, int $i_uMode = PHP_BINARY_READ ) : string {
        $bst = @socket_read( $this->socket, $i_uMaxLength, $i_uMode );
        if ( is_string( $bst ) ) {
            return $bst;
        }
        throw new ReadException( $this->socket, "Socket::read( {$i_uMaxLength}, {$i_uMode} ) failed" );
    }


    /** @param-out bool $o_bComplete */
    public function readTimed( int   $i_uExactLength, int $i_uTimeoutSeconds = 0,
                               int   $i_uTimeoutMicroSeconds = 0, int $i_uMode = PHP_BINARY_READ,
                               ?bool &$o_bComplete = null ) : string {
        $st = '';
        $o_bComplete = false;
        while ( strlen( $st ) < $i_uExactLength ) {
            if ( ! $this->selectForRead( $i_uTimeoutSeconds, $i_uTimeoutMicroSeconds ) ) {
                return $st;
            }
            $st .= $this->read( $i_uExactLength - strlen( $st ), $i_uMode );
        }
        $o_bComplete = true;
        return $st;
    }


    public function recv( ?string &$o_stData, int $i_uLength, int $i_uFlags = 0 ) : int {
        $fu = socket_recv( $this->socket, $o_stData, $i_uLength, $i_uFlags );
        if ( is_int( $fu ) ) {
            return $fu;
        }
        throw new ReadException( $this->socket, "Socket::recv( {$i_uLength}, {$i_uFlags} ) failed" );
    }


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
                              ?int    &$o_nuPort = null ) : int {
        $fu = @socket_recvfrom( $this->socket, $o_stData, $i_uLength, $i_uFlags, $o_stAddress, $o_nuPort );
        if ( is_int( $fu ) ) {
            return $fu;
        }
        throw new ReadException( $this->socket, "Socket::recvFrom( \$o_stData, {$i_uLength}, {$i_uFlags}, {$o_stAddress}, {$o_nuPort} ) failed" );
    }


    /**
     * @param mixed[] $o_rMessage
     * @param int $i_uFlags
     * @return int
     */
    public function recvMsg( array &$o_rMessage, int $i_uFlags = 0 ) : int {
        $fu = @socket_recvmsg( $this->socket, $o_rMessage, $i_uFlags );
        if ( is_int( $fu ) ) {
            return $fu;
        }
        throw new ReadException(
            $this->socket,
            "Socket::recvMsg( \$o_rMessage, {$i_uFlags} ) failed"
        );
    }


    public function recvTimed( ?string &$o_stData, int $i_uLength, int $i_uTimeoutSeconds = 0,
                               int     $i_uTimeoutMicroSeconds = 0, int $i_uFlags = 0 ) : int {
        if ( ! $this->selectForRead( $i_uTimeoutSeconds, $i_uTimeoutMicroSeconds ) ) {
            return 0;
        }
        return $this->recv( $o_stData, $i_uLength, $i_uFlags );
    }


    public function remoteAddress() : string {
        $this->getPeerName( $stAddress, $nuPort );
        /** @phpstan-ignore-next-line */
        assert( is_string( $stAddress ) );
        return $stAddress;
    }


    public function remotePort() : int {
        $this->getPeerName( $stAddress, $nuPort );
        if ( is_int( $nuPort ) ) {
            return $nuPort;
        }
        throw new LogicException( 'Asked for remote port on family that does not use ports.' );
    }


    public function selectForRead( int $i_uTimeoutSeconds = 0, int $i_uTimeoutMicroSeconds = 0 ) : bool {
        $read = [ $this->socket ];
        $write = null;
        $except = null;
        $rc = @socket_select( $read, $write, $except, $i_uTimeoutSeconds, $i_uTimeoutMicroSeconds );
        if ( is_int( $rc ) ) {
            return $rc > 0 && count( $read ) > 0;
        }
        throw new Exception( null, "Socket::selectForRead( {$i_uTimeoutSeconds}, {$i_uTimeoutMicroSeconds} ) failed" );
    }


    public function selectForWrite( int $i_uTimeoutSeconds = 0, int $i_uTimeoutMicroSeconds = 0 ) : bool {
        $read = null;
        $write = [ $this->socket ];
        $except = null;
        $rc = @socket_select( $read, $write, $except, $i_uTimeoutSeconds, $i_uTimeoutMicroSeconds );
        if ( is_int( $rc ) ) {
            return $rc > 0 && count( $write ) > 0;
        }
        throw new Exception( null, "Socket::selectForWrite( {$i_uTimeoutSeconds}, {$i_uTimeoutMicroSeconds} ) failed" );
    }


    public function send( string $i_stData, ?int $i_nuLen = null, int $i_uFlags = 0 ) : int {
        $i_nuLen ??= strlen( $i_stData );
        $fu = socket_send( $this->socket, $i_stData, $i_nuLen, $i_uFlags );
        if ( is_int( $fu ) ) {
            return $fu;
        }
        throw new WriteException( $this->socket, "Socket::send( {$i_stData}, {$i_nuLen}, {$i_uFlags} ) failed" );
    }


    /**
     * @param mixed[] $i_rMessage
     * @param int $i_uFlags
     * @return int
     */
    public function sendMsg( array $i_rMessage, int $i_uFlags = 0 ) : int {
        $fu = @socket_sendmsg( $this->socket, $i_rMessage, $i_uFlags );
        if ( is_int( $fu ) ) {
            return $fu;
        }
        $stMessage = serialize( $i_rMessage );
        throw new WriteException( $this->socket, "Socket::sendMsg( {$stMessage}, {$i_uFlags} ) failed" );
    }


    public function sendTimed( string $i_stData, ?int $i_nuLen = null, int $i_uTimeoutSeconds = 0,
                               int    $i_uTimeoutMicroSeconds = 0, int $i_uFlags = 0 ) : int {
        if ( ! $this->selectForWrite( $i_uTimeoutSeconds, $i_uTimeoutMicroSeconds ) ) {
            return 0;
        }
        return $this->send( $i_stData, $i_nuLen, $i_uFlags );
    }


    /** @suppress PhanTypeMismatchArgumentNullableInternal Port nullable since 8.0. */
    public function sendTo( string $i_stData, ?int $i_nuLen = null, string $i_stAddress = '', ?int $i_nuPort = null,
                            int    $i_uFlags = 0 ) : int {
        $i_nuLen ??= strlen( $i_stData );
        $fu = @socket_sendto( $this->socket, $i_stData, $i_nuLen, $i_uFlags, $i_stAddress, $i_nuPort );
        if ( is_int( $fu ) ) {
            return $fu;
        }
        throw new WriteException( $this->socket, "Socket::sendTo( {$i_stData}, {$i_nuLen}, {$i_stAddress}, {$i_nuPort}, {$i_uFlags} ) failed" );
    }


    public function setBlock() : void {
        if ( true === @socket_set_block( $this->socket ) ) {
            return;
        }
        throw new Exception( $this->socket, 'Socket::setBlock() failed' );
    }


    public function setNonBlock() : void {
        if ( true === @socket_set_nonblock( $this->socket ) ) {
            return;
        }
        throw new Exception( $this->socket, 'Socket::setNonBlock() failed' );
    }


    /**
     * @param int $i_uLevel
     * @param int $i_uOption
     * @param mixed[]|int|string $i_value
     * @return void
     * @throws Exception
     */
    public function setOption( int $i_uLevel, int $i_uOption, array|int|string $i_value ) : void {
        if ( true === @socket_set_option( $this->socket, $i_uLevel, $i_uOption, $i_value ) ) {
            return;
        }
        if ( is_array( $i_value ) ) {
            $i_value = serialize( $i_value );
        }
        throw new Exception( $this->socket, "Socket::setOption( {$i_uLevel}, {$i_uOption}, {$i_value} ) failed" );
    }


    public function shutdown( int $i_uHow = 2 ) : void {
        if ( true === @socket_shutdown( $this->socket, $i_uHow ) ) {
            return;
        }
        throw new ConnectionException( $this->socket, "Socket::shutdown( {$i_uHow} ) failed" );
    }


    public function socket() : \Socket {
        return $this->socket;
    }


    public function write( string $i_stData, ?int $i_nuLen = null ) : int {
        $i_nuLen ??= strlen( $i_stData );
        $fu = socket_write( $this->socket, $i_stData, $i_nuLen );
        if ( is_int( $fu ) ) {
            return $fu;
        }
        throw new WriteException( $this->socket, "Socket::write( {$i_stData}, {$i_nuLen} ) failed" );
    }


}
