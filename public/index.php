<?php
chdir('..');

require_once('framework/Request.php');
\Framework\Request::init();

require_once('framework/Route.php');

\Framework\Route::add('GET', '', 'IndexController', 'index');


\Framework\Route::add('GET', 't', function(){

    die('xx');

});

\Framework\Route::parse();