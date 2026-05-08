<?php

namespace App\Services;

class FlexMessageBuilder
{
    /**
     * Render a Flex template by interpolating {{var}} placeholders against the variables map.
     * Returns the parsed Flex payload (associative array).
     */
    public function render(string $jsonTemplate, array $variables): array
    {
        $rendered = $this->interpolate($jsonTemplate, $variables);
        $decoded = json_decode($rendered, true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }

    /**
     * Build the full MOPH Alert request payload — array of messages (a leading text + the flex bubble).
     */
    public function buildPayload(array $renderedFlex, string $textMessage, string $altText): array
    {
        // Ensure altText is set on the flex message
        $flex = $renderedFlex;
        $flex['type'] = 'flex';
        $flex['altText'] = $renderedFlex['altText'] ?? $altText;

        return [
            'messages' => [
                ['type' => 'text', 'text' => $textMessage],
                $flex,
            ],
        ];
    }

    private function interpolate(string $template, array $vars): string
    {
        return preg_replace_callback('/\{\{\s*([a-z0-9_.]+)\s*\}\}/i', function ($m) use ($vars) {
            $key = $m[1];
            $value = $this->getNested($vars, $key);
            if ($value === null) return '';
            // Escape for JSON string context: quotes, backslashes, control chars
            return $this->jsonEscape((string) $value);
        }, $template);
    }

    private function getNested(array $arr, string $dottedKey): mixed
    {
        $segments = explode('.', $dottedKey);
        $cur = $arr;
        foreach ($segments as $seg) {
            if (! is_array($cur) || ! array_key_exists($seg, $cur)) return null;
            $cur = $cur[$seg];
        }

        return $cur;
    }

    private function jsonEscape(string $value): string
    {
        // json_encode then strip surrounding quotes — handles escaping properly
        return trim(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), '"');
    }
}
