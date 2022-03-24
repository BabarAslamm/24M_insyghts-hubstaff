composer require insyghts/hubstaff

For Lumen

=> inside bootstrap/app.php

Uncomment  below two lines if these are commented out
$app->withFacades();
$app->withEloquent();

add following lines

$app->register(\Insyghts\Common\Services\CommonServiceProvider::class);
$app->register(\Insyghts\Authentication\AuthenticationServiceProvider::class);
$app->register(\Insyghts\Hubstaff\HubstaffPkgProvider::class);

configure env for database connectivity

php artisan migrate

Test in postman


