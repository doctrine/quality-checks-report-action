<?php

namespace Doctrine\GithubActions;

use DOMDocument;
use DOMXpath;

class CheckstyleParser
{
    public function parseString(string $xml, string $rootPath) : array
    {
        $dom = new DOMDocument();
        $dom->loadXML($xml);

        return $this->processDom($dom, $rootPath);
    }

    public function parseFile(string $reportFileName, string $rootPath) : array
    {
        $dom = new DOMDocument();
        $dom->load($reportFileName);

        return $this->processDom($dom, $rootPath);
    }

    private function processDom($dom)
    {
        $xpath = new DOMXpath($dom);
        $allFailures = [];

        foreach ($xpath->evaluate('//file') as $fileNode) {
            $errorNodes = $xpath->evaluate('error', $fileNode);

            foreach ($errorNodes as $errorNode) {
                $severity = $errorNode->getAttribute('severity') ?: 'error';

                $allFailures[] = [
                    'name' => $errorNode->getAttribute('source'),
                    'class' => basename($fileNode->getAttribute('name')),
                    'body' => $errorNode->getAttribute('message'),
                    'file' => ltrim(str_replace($rootPath, '', $fileNode->getAttribute('name')), '/'),
                    'line' => (int)$errorNode->getAttribute('line'),
                    'type' => $severity === 'info' ? 'notice' : 'failure',
                ];
            }
        }

        return $allFailures;
    }
}
