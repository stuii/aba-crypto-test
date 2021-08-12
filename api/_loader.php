<?php

declare(strict_types=1);

$basePath = __DIR__;

foreach (
    [
        '/vendor/autoload.php',
    ] as $file
) {
    require_once $basePath.$file;
}

spl_autoload_register(
    static function ($className): void {
        $basePath = __DIR__;
        $className = str_replace('\\', '/', $className);
        $filePath = $basePath.'/classes/'.$className.'.php';

        if ($filePath && file_exists($filePath)) {
            include_once $filePath;
        } else {
            die('Dependency not found. ('.$filePath.' / '.$className.')');
        }
    }
);

