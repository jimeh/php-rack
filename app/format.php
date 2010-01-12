<?php

class Format {
	
	function __construct (&$app) {
		$this->app =& $app;
	}
	
	function call (&$env) {
		
		// available formats
		$formats = array("txt" => "text/plain", "xml" => "application/xml");
		
		// call the next middleware in the stack
		list($status, $headers, $body) = $this->app->call($env);
		
		// do something with response headers
		foreach( $formats as $key => $value ) {
			if ( !empty($env["request.vars"]["format"]) && $env["request.get"]["format"] == $key ) {
				$headers["Content-Type"] = $value;
			}
		}
		
		return array($status, $headers, $body);
	}
	
}

?>