<?php

class App {
	
	function call (&$env) {
		if ( $env["PATH_INFO"] == "/" ) {
			return array(200, array("Content-Type" => "text/html"), array("Welcome Home"));
		} elseif ( preg_match("/^\/about\/?/i", $env["PATH_INFO"]) ) {
			return array(200, array("Content-Type" => "text/html"), array("Rack-style middleware is cool."));
		}
		return Rack::not_found();
	}
	
}

?>