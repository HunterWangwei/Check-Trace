<?php
declare(strict_types=1);

namespace App\Services;

final class UaClassifier
{
    public function classify(string $ua): string
    {
        if ($ua === '') {
            return '未知，可能来自逆向';
        }
        if (strpos($ua, 'IPS') !== false) {
            return 'Azure';
        }
        if (strpos($ua, 'OpenAI') !== false) {
            return 'OpenAI';
        }
        return '普通代理';
    }
}


