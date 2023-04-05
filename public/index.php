<?php
require_once 'loader.php';

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader
| for our application. We just need to utilize it! We'll require it
| into the script here so that we do not have to worry about the
| loading of any our classes "manually". Feels great to relax.
|
*/

use App\Helpers\Response;
use App\Http\Controllers\Reviews;
use Luracast\Restler\Explorer\v2\Explorer;
use Luracast\Restler\Restler;

/*
|--------------------------------------------------------------------------
| Configure your Web Application
|--------------------------------------------------------------------------
|
| Configure your favourite web app framework to handle web requests and
| respond back. If you are using Restler 5 framework, you may simply uncomment
| the code below and run the following command from the command line on the
| project root folder
|
|    composer require restler/framework
|
*/

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application set up, we can simply let it handle the
| request and response
|
*/
// $r = new Restler();
// $r->addAPIClass('YourClassNameHere');
// $r->handle();
if( !request()->has('help') )
{
    $url = url("?help");
    $name = config('app.name') ?: $url;
    return Response::make(sprintf("<center><a href='%s'>%s</a></center>", $url ?: "?", $name ?: "index.php"), 'none');
}

$request = request();
$request->offsetUnset('help');
$request->overrideGlobals();
$query = $request->getQueryString();
$query = ($query ? "?" : "") . $query;

collect(glob(__DIR__ . '/*.php', GLOB_BRACE))
    ->reject(fn($file) => in_array(basename($file), [
        'loader.php',
        'index.php',
    ]))
    ->each(function($file) use ($query) {
        $file = basename($file);
        $page = str_before($file, '.php');
        $page = title_case($page);
        echo "<p><a href='$file{$query}'>{$page}</a><br /><small style='background-color: #676565;color: #fff'>$file{$query}</small></p><br />";
    });
?>

<b>login.php:</b>
<pre>
    login.php?token=[token]<br>
    login.php?email=[email]&password=[password]<br>
</pre>

<br>

<b>logout.php:</b>
<pre>
    login.php?token=[token]<br>
</pre>

<br>

<b>register.php:</b>
<pre>
    register.php?email=[email]&email2=[email2]&password=[password]&name=[name]<br>
</pre>

<br>

<b>user.php:</b>
<pre>
    user.php?token=[token]<br>
</pre>

<br>

<b>verify.php:</b>
<pre>
    verify.php?hash=[hash]<br>
</pre>

<br>