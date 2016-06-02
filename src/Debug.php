<?php
# NOTICE OF LICENSE
#
# This source file is subject to the Open Software License (OSL 3.0)
# that is available through the world-wide-web at this URL:
# http://opensource.org/licenses/osl-3.0.php
#
# -----------------------
# @author: Iván Miranda
# @version: 1.0.0
# -----------------------
# Simple class for debug & logger on PHP
# -----------------------

namespace Sincco\Tools;

defined('E_DEPRECATED') || define('E_DEPRECATED', 8192);
defined('E_USER_DEPRECATED') || define('E_USER_DEPRECATED', 16384);

class Debug {
	static protected $reporting = E_ALL;
	static protected $path 		= __DIR__;

	static public    function reporting ( $reporting = null ) {
		if ( ! func_num_args() )
			return self::$reporting;
		self::$reporting = $reporting;
	}
	static public function path( $path ) {
		self::$path = $path;
	}

	static public    function log () {
		$args = func_get_args();
		if( self::$reporting ) {
			print( 
				self::style() . PHP_EOL . '<pre class="debug log">'
				. implode( 
					'</pre>' . PHP_EOL . '<pre class="log">' 
					, array_map( '\Sincco\Tools\Debug::var_export', $args )
				)
				. '</pre>'
			);
		} else {
			$message = PHP_EOL . implode( PHP_EOL, array_map( '\Sincco\Tools\Debug::var_export', $args ) );
			self::write( $message );
		}
	}

	static public function error() {
		$args = func_get_args();
		if( self::$reporting ) {
			print( 
				self::style() . PHP_EOL . '<pre class="debug log">'
				. implode( 
					'</pre>' . PHP_EOL . '<pre class="log">' 
					, array_map( '\Sincco\Tools\Debug::var_export', $args )
				)
				. '</pre>'
			);
		} else {
			$message = PHP_EOL . implode( PHP_EOL, array_map( '\Sincco\Tools\Debug::var_export', $args ) );
			self::write( $message, 'err' );
		}
	}

	static public function write( $message, $ext = 'log' ) {
		if( $log_file = fopen( self::$path . '/' . date('YW').'.'.$ext, 'a+') ) {
			fwrite($log_file, date("mdGis") . "::" . PHP_EOL);
			fwrite($log_file, $message . PHP_EOL);
			fwrite($log_file,"--------------------------------------------".PHP_EOL);
			fwrite($log_file,'URL: http://'.$_SERVER['HTTP_HOST'].':'.$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'].PHP_EOL);
			fwrite($log_file, "PHP ".phpversion()." - ".PHP_OS."(".PHP_SYSCONFDIR." ".PHP_BINARY.")".PHP_EOL);
			fwrite($log_file,"--------------------------------------------".PHP_EOL);
			fwrite($log_file,"--------------------------------------------".PHP_EOL.PHP_EOL);
			fclose($log_file);
		}
	}

	static public function dump () {
		if( ! self::$reporting ) {
    		header("HTTP/1.0 500 Not Found");
    		echo self::template500();
    	}
    	$args = func_get_args();
		die( call_user_func_array( '\Sincco\Tools\Debug::error', $args ) );
	}
	static private   $time;
	static private   $chrono;
	static public    function chrono ( $print = null, $scope = '' ) {
		if ( ! self::$reporting )
			return;
		if ( ! isset( self::$time[ $scope ] ) )
			$chrono [] = '<b class="init">' . $scope . ' chrono init</b>';
		elseif ( is_string( $print ) ) {
			$chrono[] = sprintf('<span class="time">%s -> %s: %fs</span>'
				, $scope
				, $print
				, round( self::$chrono[ $scope ][ $print ] = microtime( true ) - self::$time[ $scope ], 6 )
			);
		} elseif ( $print && isset( self::$chrono[ $scope ] ) ) {
			asort( self::$chrono[ $scope ] );
			$base = reset ( self::$chrono[ $scope ] ); // shortest duration
			foreach( self::$chrono[ $scope ] as $event => $duration )
				$table[] = sprintf( '%5u - %-38.38s <i>%7fs</i>'
					, round( $duration / $base, 2 )
					, $event
					, round( $duration, 3 )
				);
			$chrono[] = '<div class="table"><b>' . $scope . ' chrono table</b>' . PHP_EOL .
				sprintf( '%\'-61s %-46s<i>duration</i>%1$s%1$\'-61s'
					, PHP_EOL
					, 'unit - action'
				) . 
				implode( PHP_EOL, $table ) . PHP_EOL . 
				'</div>';
		}
		echo self::style(), PHP_EOL, '<pre class="debug chrono">', implode( PHP_EOL, $chrono ), '</pre>';
		return self::$time[ $scope ] = microtime( true );
	}
	static private   $registered;
	static public    function register ( $init = true ) {
		if ( $init) {
			if ( ! self::$registered )
				self::$registered = array(
					'display_errors'    => ini_get( 'display_errors' )
          			, 'error_reporting' => error_reporting()
					, 'shutdown'        => register_shutdown_function( '\Sincco\Tools\Debug::shutdown' )
				);
			self::$registered[ 'shutdown' ] = true;
			error_reporting( E_ALL );
			set_error_handler( '\Sincco\Tools\Debug::handler', E_ALL );
			set_exception_handler( '\Sincco\Tools\Debug::exception' );
			ini_set( 'display_errors', 0 );
		} elseif ( self::$registered ) {
			self::$registered[ 'shutdown' ] = false;
			error_reporting( self::$registered[ 'error_reporting' ] );
			restore_error_handler();
			restore_exception_handler();
			ini_set( 'display_errors', self::$registered[ 'display_errors' ] );
		}
	}
	static protected $error     = array(
		-1                    => 'Exception'
		, E_ERROR             => 'Fatal'
		, E_RECOVERABLE_ERROR => 'Recoverable'
		, E_WARNING           => 'Warning'
		, E_PARSE             => 'Parse'
		, E_NOTICE            => 'Notice'
		, E_STRICT            => 'Strict'
		, E_DEPRECATED        => 'Deprecated'
		, E_CORE_ERROR        => 'Fatal'
		, E_CORE_WARNING      => 'Warning'
		, E_COMPILE_ERROR     => 'Compile Fatal'
		, E_COMPILE_WARNING   => 'Compile Warning'
		, E_USER_ERROR        => 'Fatal'
		, E_USER_WARNING      => 'Warning'
		, E_USER_NOTICE       => 'Notice'
		, E_USER_DEPRECATED   => 'Deprecated'
	);
	static public    function handler ( $type, $message, $file, $line, $scope, $stack = null ) {
		global $php_errormsg; // set global error message regardless track errors settings
		$php_errormsg = preg_replace( '~^.*</a>\]: +(?:\([^\)]+\): +)?~', null, $message ); // clean useless infos
		if ( ! self::$reporting ) // de-activate
			return false;
		$stack = $stack ? $stack : array_slice( debug_backtrace( false ),  $type & E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE ?  2 : 1 ); // clean stack depending if error is user triggered or not
		self::overload( $stack, $file, $line  ); // switch line & file if overloaded method triggered the error
		echo self::style(), PHP_EOL, '<pre class="debug error ', strtolower( self::$error[ $type ] ), '">', PHP_EOL, 
			sprintf( '<b>%s</b>: %s in <b>%s</b> on line <b>%s</b>'  // print error
				, self::$error[ $type ] ? self::$error[ $type ] : 'Error'
				, $php_errormsg
				, $file
				, $line
			);
		if ( $type & self::$reporting ) // print context
			echo self::context( $stack, $scope );
		echo '</pre>';
		if ( $type & E_USER_ERROR ) // fatal
			exit;
	}
	static public    function shutdown () {
		if ( self::$registered[ 'shutdown' ] && ( $error = error_get_last() ) && ( $error[ 'type' ] & ( E_ERROR |  E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR ) ) )
			self::handler( $error[ 'type' ], $error[ 'message' ], $error[ 'file' ], $error[ 'line' ], null );
	}
	static public    function exception ( Exception $exception ) {
		$msg = sprintf( '"%s" with message "%s"', get_class( $exception ), $exception->getMessage() );
		self::handler( -1, $msg, $exception->getFile(), $exception->getLine(), null, $exception->getTrace() );
	}
	static public    $style     = array(
		'debug'         => 'font-size:1em;padding:.5em;border-radius:5px'
		, 'error'       => 'background:#eee'
		, 'exception'   => 'color:#825'
		, 'parse'       => 'color:#F07'
		, 'compile'     => 'color:#F70'
		, 'fatal'       => 'color:#F00'
		, 'recoverable' => 'color:#F22'
		, 'warning'     => 'color:#E44'
		, 'notice'      => 'color:#E66'
		, 'deprecated'  => 'color:#F88'
		, 'strict'      => 'color:#FAA'
		, 'stack'       => 'padding:.2em .8em;color:#444'
		, 'trace'       => 'border-left:1px solid #ccc;padding-left:1em'
		, 'scope'       => 'padding:.2em .8em;color:#666'
		, 'var'         => 'border-bottom:1px dashed #aaa;margin-top:-.5em;padding-bottom:.9em'
    	, 'log'         => 'background:#f7f7f7;color:#33e'
		, 'chrono'      => 'border-left:2px solid #ccc'
		, 'init'        => 'color:#4A6'
		, 'time'        => 'color:#284'
		, 'table'       => 'color:#042'
	);
	static protected function style () {
		static $style;
		if ( $style )
			return;
		foreach ( self::$style as $class => $css )
			$style .= sprintf( '.%s{%s}', $class, $css );
		return PHP_EOL . '<style type="text/css">' . $style . '</style>';
	}
	static protected $overload  = array(
		'__callStatic'   => 2
		, '__call'       => 2
		, '__get'        => 1
		, '__set'        => 1
		, '__clone'      => 1
		, 'offsetGet'    => 1
		, 'offsetSet'    => 1
		, 'offsetUnset'  => 1
		, 'offsetExists' => 1
	);
	static protected function overload ( &$stack, &$file, &$line ) {
		if ( isset( $stack[ 0 ][ 'class' ], self::$overload[ $stack[ 0 ][ 'function' ] ] ) && $offset = self::$overload[ $stack[ 0 ][ 'function' ] ] )
			for ( $i = 0; $i < $offset; $i++ )
				extract( array_shift( $stack ) ); // clean stack and overwrite file & line
	}
	static protected function context ( $stack, $scope ) {
		if ( ! $stack )
				return;
		$context[] = PHP_EOL . '<div class="stack"><i>Stack trace</i> :';
		foreach ( $stack as $index => $call )
			$context[] = sprintf( '  <span class="trace">#%s %s: <b>%s%s%s</b>(%s)</span>'
				, $index
				, isset( $call[ 'file' ] )  ? $call[ 'file' ] . ' (' . $call[ 'line' ] . ')' : '[internal function]'
				, isset( $call[ 'class' ] ) ? $call[ 'class' ]                              : ''
				, isset( $call[ 'type' ] )  ? $call[ 'type' ]                               : ''
				, $call[ 'function' ]
				, isset( $call[ 'args' ] )  ? self::args_export( $call[ 'args' ] )          : ''
			);
		$context[] = '  <span class="trace">#' . ( $index + 1 ) . ' {main}</span>'; 
		$context[] = '</div><div class="scope"><i>Scope</i> :';
    $vars = '';
		if ( isset( $scope['GLOBALS'] ) )
			$vars = '  GLOBAL';
		elseif ( ! $scope )
			$vars = '  NONE';
		else
			foreach ( (array) $scope as $name => $value )
				$vars .= '  <div class="var">$' . $name .' = ' . self::var_export( $value ) . ';' . PHP_EOL . '</div>';
		$context[] = $vars . '</div>';
		return implode( PHP_EOL, $context );
	}
	static protected function var_export ( $var ) {
    ob_start();
    var_dump( $var );
    $export = ob_get_clean();
    $export = preg_replace( '/\s*\bNULL\b/m', ' null', $export ); // Cleanup NULL
    $export = preg_replace( '/\s*\bbool\((true|false)\)/m', ' $1', $export ); // Cleanup booleans
    $export = preg_replace( '/\s*\bint\((\d+)\)/m', ' $1', $export ); // Cleanup integers
    $export = preg_replace( '/\s*\bfloat\(([\d.e-]+)\)/mi', ' $1', $export ); // Cleanup floats
    $export = preg_replace( '/\s*\bstring\(\d+\) /m', '', $export ); // Cleanup strings
    $export = preg_replace( '/object\((\w+)\)(#\d+) \(\d+\)/m', '$1$2', $export ); // Cleanup objects definition
    //@todo array cleaning
    $export = preg_replace( '/=>\s*/m', '=> ', $export ); // No new line between array/object keys and properties
    $export = preg_replace( '/\[([\w": ]+)\]/', ', $1 ', $export ); // remove square brackets in array/object keys
    $export = preg_replace( '/([{(]\s+), /', '$1  ', $export ); // remove first coma in array/object properties listing
    $export = preg_replace( '/\{\s+\}/m', '{}', $export );
    $export = preg_replace( '/\s+$/m', '', $export ); // Trim end spaces/new line
    return $export;
	}
	static protected function simple_export ( $var ) {
		$export = self::var_export( $var );
		if ( is_array( $var ) ) {
			$export  = preg_replace( '/\s+\d+ => /m', ', ', $export );
			$export  = preg_replace( '/\s+(["\w]+ => )/m', ', $1', $export );
			$pattern = '#array\(\d+\) \{[\s,]*([^{}]+|(?R))*?\s+\}#m';
			while ( preg_match( $pattern, $export ) )
			$export  = preg_replace( $pattern, 'array($1)', $export );
			return $export;
		}
		if ( is_object( $var ) )if( self::$reporting ) {
			return substr( $export, 0, strpos( $export, '#' ) ); // strstr( $export, '#', true );
		return $export;
		}
	}
	static protected function args_export ( $args ) {
		return implode(', ', array_map( 
			'\Sincco\Tools\Debug::simple_export', 
			(array) $args
		) );
	}
	static protected function template500() {
		return '<!DOCTYPE html><html lang="es"><head> <meta charset="utf-8"> <meta http-equiv="X-UA-Compatible" content="IE=edge"> <meta name="viewport" content="width=device-width, initial-scale=1"> <meta name="description" content=""> <meta name="author" content=""> <title>Error interno</title> <script type="text/javascript" src="https://code.jquery.com/jquery-2.2.1.min.js"></script> <script type="text/javascript" src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script> <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css"> <link href="https://fonts.googleapis.com/css?family=Baumans" rel="stylesheet" type="text/css"> <style type="text/css">.logo h1,.logo p{text-align:center}body{font-family:Baumans,cursive;background:#CEE3F5}.wrap{margin:0 auto;width:1000px}.logo{margin-top:50px}.logo h1{font-size:200px;color:#8F8E8C;margin-bottom:1px;text-shadow:1px 1px 6px #fff}.logo p{color:#445967;font-size:16px;margin-top:1px}.logo p span{color:#90ee90}.sub a{color:#fff;background:#8F8E8C;text-decoration:none;padding:7px 120px;font-size:13px;font-family:arial,serif;font-weight:700;-webkit-border-radius:3em;-moz-border-radius:.1em;-border-radius:.1em}.footer{color:#8F8E8C;position:absolute;right:10px;bottom:10px}.footer a{color:#e492a2}</style></head><body id="page-top" class="index"><div class="container"><div class="row"><div class="col-md-12"><div class="wrap"> <div class="logo"><h1>500</h1><p><strong>Error interno</strong></p><p>Revisa el log para conocer el motivo</p></div></div></div></div></div></body></html>';
	}
}

Debug::register();
if ( ! function_exists( 'l' ) ) {
  function l () {
    $args = func_get_args();
    call_user_func_array( '\Sincco\Tools\Debug::log', $args );
  }
}
if ( ! function_exists( 'd' ) ) {
  function d () {
    $args = func_get_args();
  	call_user_func_array( '\Sincco\Tools\Debug::dump', $args );
  }
}
if ( ! function_exists( 'c' ) ) {
  function c () {
    $args = func_get_args();
  	call_user_func_array( '\Sincco\Tools\Debug::chrono', $args );
  }
}