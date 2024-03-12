<?php
// src/Service/JitsiMeetService.php

namespace App\Service;

class JitsiMeetService
{
    private $domain;

    public function __construct(string $domain = 'meet.jit.si')
    {
        $this->domain = $domain;
    }

    public function generateJitsiConfig(string $roomName, array $options = []): array
    {
        $defaultOptions = [
            'roomName' => $roomName,
            'width' => '100%',
            'height' => 700,
            'parentNode' => null,
            'configOverwrite' => [],
            'interfaceConfigOverwrite' => [],
            'noSsl' => false
        ];

        $config = array_merge($defaultOptions, $options);

        // Vous pouvez ajouter ici d'autres logiques pour personnaliser la configuration.

        return $config;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }
}