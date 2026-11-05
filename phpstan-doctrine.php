<?php

declare(strict_types=1);

use App\Kernel;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/vendor/autoload.php';

new Dotenv()->loadEnv(__DIR__.'/.env.test');

$kernel = new Kernel('test', false);
$kernel->boot();

$container = $kernel->getContainer();

if ($container->has('test.service_container')) {
    $container = $container->get('test.service_container');
}

/** @var ManagerRegistry $doctrine */
$doctrine = $container->get(ManagerRegistry::class);

return $doctrine->getManager();
