<?php

namespace PiedWeb\UrlHarvester;

use InvalidArgumentException;

/**
 * Waiting validation for PR :
 * https://github.com/spatie/robots-txt/pull/21.
 */
class RobotsHeaders
{
    protected $robotHeadersProperties = [];

    public static function readFrom(string $source): self
    {
        $content = @file_get_contents($source);

        if (false === $content) {
            throw new InvalidArgumentException("Could not read from source `{$source}`");
        }

        return new self($http_response_header ?? []);
    }

    public static function create(array $headers): self
    {
        return new self($headers);
    }

    public function __construct(array $headers)
    {
        $this->robotHeadersProperties = $this->parseHeaders($headers);
    }

    public function mayIndex(string $userAgent = '*'): bool
    {
        return !$this->noindex($userAgent);
    }

    public function mayFollow(string $userAgent = '*'): bool
    {
        return !$this->nofollow($userAgent);
    }

    public function noindex(string $userAgent = '*'): bool
    {
        return
            // 1. We check for the suggested user-agent
            $this->robotHeadersProperties[$userAgent]['noindex']
            // 2. We check for all user-agent
            ?? $this->robotHeadersProperties['*']['noindex']
            // 3. noindex doesn't exist, so return fasle
            ?? false;
    }

    public function nofollow(string $userAgent = '*'): bool
    {
        return
            $this->robotHeadersProperties[$userAgent]['nofollow']
            ?? $this->robotHeadersProperties['*']['nofollow']
            ?? false;
    }

    protected function parseHeaders(array $headers): array
    {
        $robotHeaders = $this->filterRobotHeaders($headers);

        return array_reduce($robotHeaders, function (array $parsedHeaders, $header) {
            $header = $this->normalizeHeaders($header);

            $headerParts = explode(':', $header);

            $userAgent = 3 === count($headerParts)
                ? trim($headerParts[1])
                : '*';

            $options = end($headerParts);

            $parsedHeaders[$userAgent] = [
                'noindex' => false !== strpos(strtolower($options), 'noindex'),
                'nofollow' => false !== strpos(strtolower($options), 'nofollow'),
            ];

            return $parsedHeaders;
        }, []);
    }

    protected function filterRobotHeaders(array $headers): array
    {
        return array_filter($headers, function ($header) use ($headers) {
            $headerContent = $this->normalizeHeaders($headers[$header] ?? []);

            return 0 === strpos(strtolower($header), 'x-robots-tag')
                || 0 === strpos(strtolower($headerContent), 'x-robots-tag');
        }, ARRAY_FILTER_USE_KEY);
    }

    protected function normalizeHeaders($headers): string
    {
        return implode(',', (array) $headers);
    }
}
