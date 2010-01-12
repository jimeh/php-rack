<?php

class Api {
	
	function __construct (&$app) {
		$this->app =& $app;
	}
	
	function call (&$env) {
		if ( preg_match("/^\/api\/([a-z]{1}[a-z0-9]*)/i", $env["PATH_INFO"], $match) ) {
			return array(200, array("Content-Type" => "text/json"), array("{api_call: \"".$match[1]."\"}"));
		} else {
			return $this->app->call($env);
		}
	}
	
}

?>