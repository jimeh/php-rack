<?php
/*

   PHP Rack v0.1.0

   Copyright (c) 2010 Jim Myhrberg.

   Permission is hereby granted, free of charge, to any person obtaining
   a copy of this software and associated documentation files (the
   'Software'), to deal in the Software without restriction, including
   without limitation the rights to use, copy, modify, merge, publish,
   distribute, sublicense, and/or sell copies of the Software, and to
   permit persons to whom the Software is furnished to do so, subject to
   the following conditions:

   The above copyright notice and this permission notice shall be
   included in all copies or substantial portions of the Software.

   THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND,
   EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
   MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
   IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
   CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
   TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
   SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

class Rack {
	
	public static
		$middleware = array(),
		$env = array();
	
	private static
		$constructed = false,
		$ob_started = false;
	
	
	public static function init ($middleware = array()) {
		
		// easy initialization
		if ( !empty($middleware) && is_array($middleware) ) {
			$ware = array();
			foreach( $middleware as $key => $value ) $ware[$value] = true;
			self::$middleware = array_merge(self::$middleware, $ware);
		}
		
		// don't output anything before Rack has output it's headers
		ob_start();
		self::$ob_started = true;
	}
	
	
	public static function add ($name, $file = null) {
		if ( !self::$ob_started ) self::init();
		if ( !self::$constructed ) {
			self::$middleware[$name] = true;
			self::require_file($file);
			return true;
		}
		return false;
	}
	
	
	public static function insert_before ($target, $name, $file = null) {
		if ( !self::$constructed ) {
			if ( array_key_exists($target, self::$middleware) ) {
				$keys = array_keys(self::$middleware);
				$length = count($keys);
				$middleware = array();
				for ( $i=0; $i < $length; $i++ ) {
					if ( $keys[$i] == $target ) {
						$middleware[$name] = true;
					}
					$middleware[$keys[$i]] =& self::$middleware[$keys[$i]];
				}
				self::$middleware = $middleware;
				self::require_file($file);
				return true;
			}
		}
		return false;
	}
	
	
	public static function insert_after ($target, $name, $file = null) {
		if ( !self::$constructed ) {
			if ( array_key_exists($target, self::$middleware) ) {
				$keys = array_keys(self::$middleware);
				$length = count($keys);
				$middleware = array();
				for ( $i=0; $i < $length; $i++ ) {
					$middleware[$keys[$i]] =& self::$middleware[$keys[$i]];
					if ( $keys[$i] == $target ) {
						$middleware[$name] = true;
					}
				}
				self::$middleware = $middleware;
				self::require_file($file);
				return false;
			}
		}
		return false;
	}
	
	
	public static function swap ($target, $name, $file = null) {
		if ( !self::$constructed ) {
			if ( array_key_exists($target, self::$middleware) ) {
				$keys = array_keys(self::$middleware);
				$length = count($keys);
				$middleware = array();
				for ( $i=0; $i < $length; $i++ ) {
					if ( $keys[$i] == $target ) {
						$middleware[$name] = true;
					} else {
						$middleware[$keys[$i]] =& self::$middleware[$keys[$i]];
					}
				}
				self::$middleware = $middleware;
				self::require_file($file);
				return false;
			}
		}
		return false;
	}
	
	
	public static function not_found () {
		return array(404, array("Content-Type" => "text/html"), "Not Found");
	}
	
	
	public static function run () {
		
		// build ENV
		self::$env =& $_SERVER;
		if ( strstr($_SERVER['REQUEST_URI'], '?') ) {
			self::$env["PATH_INFO"] = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '?'));
		} else {	
			self::$env["PATH_INFO"] = $_SERVER['REQUEST_URI'];
		}
		self::$env["request.vars"] =& $_REQUEST;
		self::$env["request.get"] =& $_GET;
		self::$env["request.post"] =& $_POST;
		self::$env["request.files"] =& $_FILES;
		self::$env["request.method"] =& $_SERVER["REQUEST_METHOD"];
		self::$env["cookies"] =& $_COOKIE;
		
		// construct middlewares
		self::$constructed = true;
		$middleware = array_reverse(self::$middleware);
		$previous = null;
		foreach( $middleware as $key => $value ) {
			self::$middleware[$key] = new $key($previous);
			$previous =& self::$middleware[$key];
		}
		
		// call the middleware stack
		reset(self::$middleware);
		$first = current(array_keys(self::$middleware));
		list($status, $headers, $body) = self::$middleware[$first]->call(self::$env);
		
		// send headers
		header(self::$env["SERVER_PROTOCOL"]." ".$status);
		foreach( $headers as $key => $value ) {
			header($key.": ".$value);
		}
		
		// output any buffered content from middlewares
		$buffer = ob_get_contents();
		ob_end_clean();
		if ( !empty($buffer) ) {
			echo $buffer;
		}
		
		// output body
		if ( is_array($body) ) {
			echo implode("", $body);
		} else {
			echo $body;
		}
	}
	
	
	private static function require_file ($file = null) {
		if ( $file != null && is_file($file) ) {
			require($file);
		}
	}
	
}

class RackMiddleware {
	
	public function __construct (&$app) {
		$this->app =& $app;
	}
	
}

?>