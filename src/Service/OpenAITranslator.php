<?php

namespace App\Service;

use App\Twig\AppExtension;
use App\Entity\CandidateProfile;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class OpenAITranslator
{
    private $projectDir;

    public function __construct(
        private HttpClientInterface $client, 
        private AppExtension $appExtension, 
        private string $apiKey,
        ParameterBagInterface $params
    ){
        $this->projectDir = $params->get('kernel.project_dir');
    }

    public function translate(string $text, string $sourceLang, string $targetLang): string
    {
        $segmentSize = 1024; // Définir en fonction de la limite max_tokens
        $segments = $this->splitTextIntoSegments($text, 450);
        $translatedText = '';

        foreach ($segments as $segment) {
            try {
                $response = $this->client->request('POST', 'https://api.openai.com/v1/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                    ],
                    'json' => [
                        'model' => 'text-davinci-003',
                        'prompt' => "Please translate the following text from {$sourceLang} to {$targetLang}, but do not translate the HTML tags and their attributes: '{$segment}'",
                        'max_tokens' => $segmentSize,
                    ],
                    'timeout' => 60,
                ]);

                $content = $response->toArray();
                $translatedSegment = $content['choices'][0]['text'] ?? '';
                $translatedText .= $translatedSegment;

            } catch (\Exception $e) {
                // Gérer l'exception ou loguer l'erreur
                return 'Error: ' . $e->getMessage();
            }
        }

        return $translatedText;
    }

    public function translateCategory(string $text, string $sourceLang, string $targetLang): string
    {
        try {
            $response = $this->client->request('POST', 'https://api.openai.com/v1/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'json' => [
                    'model' => 'text-davinci-003',
                    'prompt' => "Please translate the following text from {$sourceLang} to {$targetLang} : '{$text}'",
                    'max_tokens' => 1024,
                ],
                'timeout' => 60,
            ]);

            $content = $response->toArray();
            $translatedSegment = $content['choices'][0]['text'] ?? '';

            return $translatedSegment;

        } catch (\Exception $e) {
            // Gérer l'exception ou loguer l'erreur
            return 'Error: ' . $e->getMessage();
        }

    }

    public function generateDescription(string $text)
    {
        try {
            $response = $this->client->request('POST', 'https://api.openai.com/v1/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'json' => [
                    'model' => 'text-davinci-003',
                    'prompt' => "Write in french a creative and informative description for an AI tools category named '{$text}':",
                    'max_tokens' => 1024,
                ],
                'timeout' => 60,
            ]);

            $content = $response->toArray();
            $description = $content['choices'][0]['text'] ?? '';

        } catch (\Exception $e) {
            // Gérer l'exception ou loguer l'erreur
            return 'Error: ' . $e->getMessage();
        }

        return $description;
    }

    private function splitTextIntoSegments($text, $maxTokens = 1024) 
    {
        $segments = [];
        $currentSegment = '';
        $buffer = '';
    
        foreach (explode(' ', $text) as $word) {
            // Ajouter le mot au buffer
            $buffer .= $word . ' ';
    
            // Vérifier si le buffer contient un point de découpe
            if (strpos($word, '.') !== false || strpos($word, '</p>') !== false) {
                // Vérifier le nombre de tokens du segment actuel avec le buffer
                if ($this->appExtension->countTokens($currentSegment . $buffer) > $maxTokens) {
                    // Le segment actuel + le buffer dépasse la limite, sauvegarder le segment actuel
                    $segments[] = trim($currentSegment);
                    $currentSegment = $buffer;
                    $buffer = '';
                } else {
                    // Ajouter le buffer au segment actuel
                    $currentSegment .= $buffer;
                    $buffer = '';
                }
            }
        }
    
        // Ajouter le dernier segment et buffer s'ils ne sont pas vides
        if (!empty(trim($currentSegment . $buffer))) {
            $segments[] = trim($currentSegment . $buffer);
        }
    
        return $segments;
    }

    public function trans($text) {
        $scriptPath = $this->projectDir . '/assets/node_app/index.js';
        $command = sprintf('node %s %s %s', escapeshellarg($scriptPath), escapeshellarg($text), escapeshellarg($this->apiKey));
        exec($command, $output, $return_var);
    
        if ($return_var === 0) {
            return implode("\n", $output);
        } else {
            return "Erreur lors de l'exécution du script index.js";
        }
    }

    public function parse(CandidateProfile $candidateProfile)
    {
        $scriptPath = $this->projectDir . '/assets/node_app/parse.js';
        $command = sprintf('node %s %s %s', escapeshellarg($scriptPath), escapeshellarg('https://app.olona-talents.com/uploads/cv/'. $candidateProfile->getCv()), escapeshellarg($this->apiKey));
        exec($command, $output, $return_var);

        if ($return_var === 0) {
            return implode("\n", $output);
        } else {
            return "Erreur lors de l'exécution du script parse.js";
        }
    }

    public function report(CandidateProfile $candidateProfile)
    {
        $scriptPath = $this->projectDir . '/assets/node_app/report.js';
        $command = sprintf('node %s %s %s', escapeshellarg($scriptPath), escapeshellarg($candidateProfile->getTesseractResult()), escapeshellarg($this->apiKey));
        exec($command, $output, $return_var);

        if ($return_var === 0) {
            return implode("\n", $output);
        } else {
            return "Erreur lors de l'exécution du script report.js";
        }
    }

}
