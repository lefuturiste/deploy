<?php
/*
|--------------------------------------------------------------------------
| Api routing
|--------------------------------------------------------------------------
|
| Register it all your api routes
|
*/

$app->post('/new-event/{domain}', [\App\Controllers\EventApiController::class, 'postNew'])->setName('new_event');
