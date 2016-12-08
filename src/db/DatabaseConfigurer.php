<?php
namespace Sil\SilAuth\db;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Sil\PhpEnv\Env;

class DatabaseConfigurer
{
    public static function init()
    {
        $capsule = new Capsule;

        $capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => Env::get('MYSQL_HOST'),
            'database'  => Env::get('MYSQL_DATABASE'),
            'username'  => Env::get('MYSQL_USER'),
            'password'  => Env::get('MYSQL_PASSWORD'),
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ]);

        // Set the event dispatcher used by Eloquent models.
        $capsule->setEventDispatcher(new Dispatcher(new Container));

        // Make this Capsule instance available globally via static methods.
        $capsule->setAsGlobal();
        
        // Setup the Eloquent ORM.
        $capsule->bootEloquent();
    }
}
