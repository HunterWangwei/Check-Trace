<?php
declare(strict_types=1);

namespace App\Controllers;

final class HomeController
{
    public function index(): string
    {
        ob_start();
        include __DIR__ . '/../../views/home.php';
        return (string)ob_get_clean();
    }
}


