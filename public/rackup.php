<?php

define("ROOT", dirname(dirname(__FILE__)));

require(ROOT."/lib/rack.php");

// add some middlewares
Rack::add("Format", ROOT."/app/format.php");
Rack::add("App", ROOT."/app/app.php");

// insert the Api middleware before App
Rack::insert_before("App", "Api", ROOT."/app/api.php");

Rack::run();

?>