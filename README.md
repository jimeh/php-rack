# PHP Rack

This is a quick 90 minute experiment to see if a execution stack along the lines of the [Ruby][ruby]'s [Rack][rack] library is efficiently doable in PHP, and if it can actually be useful. This project serves both as a development area for the Rack class, and as an example of how it can be used.


## About Rack

[Rack][rack] is basically a webserver interface for [Ruby][ruby]. It sits between a HTTP server and Ruby application or framework, passing requests into the application and handling the output. Check the Rack [website][rack] for more information.


## Why Rack for PHP?

What I really like in Rack aside from it just being awesome, is the execution stack system it uses. It enables multiple layers of "middleware" to intercept and alter parameters of the incoming request, and the headers and body responses before being sent to the browser.

Hence, I was curious to see just how easy/difficult a similar middleware stack would be to implement in PHP. As it turns out, I had it working in 45 minutes, and another 45 minutes later it was "decently" robust.


## How It Works

The basic concept is simple. You have a list of class names, which is your Rack stack, when the stack is executed, all classes on the stack are initialized, and then the `call` method is called on the first class in the stack with a single parameter containing request information. This first class can then decide based on request info if it should return content, or pass things along to the next class on the stack. If it passes thing along to the next class on the stack, it can alter the status, headers, and body returned from the next stack item before returning it to be output to the browser.


## Hello World Example

    <?php
    
    require("lib/rack.php");
    
    class App {
    	function call (&$env) {
            return array(200, array("Content-Type" => "text/html"), array("Hello World"));
    	}
    }
    
    Rack::add("App");
    Rack::run();
    
    ?>


## Rack Middleware types

There's two basic forms of Rack Middleware (or classes) you can use with Rack. The simplest is what I'm calling "Application Classes", which basically just returns some content, like the hello world example above.

The second type I'm calling "Middleware Classes", they basically determine if they should do something with the input request itself before passing it along to the next item on the Rack stack, or if they should modify the output from the next item on the stack, like `app/format.php` does. And alternatively they can even decide if they should halt the execution stack with themselves, and return their own output, like `app/api.php` does.


## Middleware Example

A "Middleware Class" works just like the hello world example above, except it also needs a `__construct` class. When each class on the stack are initialized, they are passed the initialized object of the stack item after itself. This allows the `call` method to use `$this->app->call($env);` to pass on execution to the next stack item.

Bellow is a simple Middleware class example, which builds on to the hello world example from above.

    <?php
    Rack::insert_before("App", "FormatXml");
    class FormatXml {
    	function __construct (&$app) {
    		$this->app =& $app;
    	}
    	function call (&$env) {
    		list($status, $headers, $body) = $this->app->call($env);
			if ( !empty($env["request.vars"]["format"]) && $env["request.get"]["format"] == "xml" ) {
				$headers["Content-Type"] = "application/xml";
			}
    		return array($status, $headers, $body);
    	}
    }
    ?>

All this middleware does is check if there is a GET or a POST variable by the name of `format`, and if it is set to `xml`. If it is, it alters the `Content-Type` header to `application/xml` before finishing and returning an array with the HTML status code, headers, and body content.


## Public Rack Methods

* `Rack::add($name, $file = null)` — Add middleware class with name `$name`, and require file `$file` if set, and file exists.
* `Rack::insert_before($target, $name, $file = null)` — Same as `Rack::add()`, except that instead of placing the new middleware at the end of the stack, it is inserted before the middleware `$name`.
* `Rack::insert_after($target, $name, $file = null)` — Same as `Rack::insert_before()`, except new middleware is inserted after `$target`.
* `Rack::swap($target, $name, $file = null)` — Replace `$target` middleware with `$name`.
* `Rack::init($middleware = array())` — Can optionally be used as a simpler way to start Rack, as it takes an array of strings representing the stack classes. It also starts output buffering, so anything echoed within a stack class, will not be output till after Rack has output it's headers. Is also automatically called to start output buffering if needed the first time you call `Rack::add()`.
* `Rack::not_found()` — A shortcut method you can use when you want to return a 404 Not Found error status/page. It simply returns `array(404, array("Content-Type" => "text/html"), "Not Found")`.
* `Rack::run()` — Initiate and execute the Rack stack.


## Running This Project

As I mentioned earlier, this project is in itself an example of how to use Rack. To try the examples, you will need to serve the public folder with Apache as the root of a virtual host, with mod_rewrite enabled. Then try visiting `/`, `/about`, `/api/hello`, and `/api/foobar`, after which you can try adding `?format=txt` to end of each of those URLs.


## Notes & Feedback

This project is a quick experiment, but I want to hear some feedback on what people think of writing PHP apps in this manner.


## License

(The MIT License)

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




[rack]: http://rack.rubyforge.org/
[ruby]: http://www.ruby-lang.org/