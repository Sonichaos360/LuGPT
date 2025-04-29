<?php

/**
 * LuGPT - a PHP library for interacting with the OpenAI API.
 *
 * @package   Sonichaos360/LuGPT
 * @author    Luciano Joan Vergara
 * @license   MIT License (https://opensource.org/licenses/MIT)
 * @link      https://github.com/Sonichaos360/LuGPT
 */

namespace Sonichaos360\LuGPT;

class Responses
{
    protected $apiKey;
    protected $model;
    protected $logPath;
    protected $bypassSSL;

    /**
     * Constructor for Responses class
     *
     * @param string $apiKey API key for OpenAI
     * @param string $model The model to use for OpenAI (gpt-4o is recommended for web search)
     * @param string|null $logPath The path to store logs, null by default
     * @param bool $bypassSSL Whether to bypass SSL certificate verification (default: false)
     */
    public function __construct($apiKey, $model = 'gpt-4o', $logPath = null, $bypassSSL = false)
    {
        if (!extension_loaded('curl')) {
            throw new \RuntimeException('cURL library is not available in this PHP installation.');
        }

        if (!isset($apiKey)) {
            throw new \InvalidArgumentException('API key is not set.');
        }

        $this->model = $model;
        $this->apiKey = $apiKey;
        $this->logPath = $logPath;
        $this->bypassSSL = $bypassSSL;
    }

    /**
     * Send a cURL request
     *
     * @param string $url The request URL
     * @param array $headers Request headers
     * @param array $postFields POST fields
     * @return mixed The API response
     */
    public function sendCurlRequest($url, $headers, $postFields)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postFields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Optionally bypass SSL certificate verification
        if ($this->bypassSSL) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $result = curl_exec($ch);

        if ($result === false) {
            throw new \RuntimeException('Curl error: ' . curl_error($ch));
        }

        curl_close($ch);

        // Log the request if logPath is set
        if (isset($this->logPath)) {
            file_put_contents($this->logPath, date('Y-m-d H:i:s') . " \n" . $url . "REQUEST: \n" . json_encode($postFields) . "\n RESPONSE:" . $result, FILE_APPEND | LOCK_EX);
        }

        return $result;
    }

    /**
     * Create a response with web search capability
     *
     * @param string $input The user's input prompt
     * @param array $options Additional options (userLocation, searchContextSize, forceWebSearch)
     * @return array The API response
     */
    public function createWithWebSearch($input, array $options = [])
    {
        $url = 'https://api.openai.com/v1/responses';
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ];

        $toolConfig = [
            'type' => 'web_search_preview'
        ];

        // Add user location if provided
        if (!empty($options['userLocation'])) {
            $location = $options['userLocation'];
            $toolConfig['user_location'] = [
                'type' => 'approximate'
            ];
            
            if (!empty($location['country'])) {
                $toolConfig['user_location']['country'] = $location['country'];
            }
            if (!empty($location['city'])) {
                $toolConfig['user_location']['city'] = $location['city'];
            }
            if (!empty($location['region'])) {
                $toolConfig['user_location']['region'] = $location['region'];
            }
            if (!empty($location['timezone'])) {
                $toolConfig['user_location']['timezone'] = $location['timezone'];
            }
        }

        // Add search context size if provided
        if (!empty($options['searchContextSize']) && in_array($options['searchContextSize'], ['low', 'medium', 'high'])) {
            $toolConfig['search_context_size'] = $options['searchContextSize'];
        }

        $postFields = [
            'model' => $this->model,
            'tools' => [$toolConfig],
            'input' => $input,
        ];

        // Add tool_choice if specified to force web search
        if (!empty($options['forceWebSearch']) && $options['forceWebSearch'] === true) {
            $postFields['tool_choice'] = ['type' => 'web_search_preview'];
        }

        $response = json_decode($this->sendCurlRequest($url, $headers, $postFields), true);
        
        return $response;
    }

    /**
     * Extract the output text from a web search response
     *
     * @param array $response The API response from createWithWebSearch
     * @return string The extracted output text
     */
    public function getOutputText($response)
    {
        if(isset($response['output']) && count($response['output']) > 0)
        {
            foreach ($response['output'] as $item) {
                if ($item['type'] === 'message' && isset($item['content'][0]['text'])) {
                    return $item['content'][0]['text'];
                }
            }
        }
        
        return [];
    }

    /**
     * Extract citations from a web search response
     *
     * @param array $response The API response from createWithWebSearch
     * @return array The extracted citations
     */
    public function getCitations($response)
    {
        if(isset($response['output']) && count($response['output']) > 0)
        {
            foreach ($response['output'] as $item) {
                if ($item['type'] === 'message' && isset($item['content'][0]['annotations'])) {
                    return $item['content'][0]['annotations'];
                }
            }
        }
        
        return [];
    }

    /**
     * Create a response with structured output using JSON Schema
     *
     * @param array $input The input messages (array of ['role' => ..., 'content' => ...])
     * @param array $schema The JSON Schema to enforce
     * @param array $options Additional options: name, description, strict, max_output_tokens, etc.
     * @return array The API response
     */
    public function createWithStructuredOutput(array $input, array $schema, array $options = [])
    {
        $url = 'https://api.openai.com/v1/responses';
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ];

        // 'name' is required for text.format in OpenAI's API
        $format = [
            'type' => 'json_schema',
            'schema' => $schema,
            'strict' => isset($options['strict']) ? (bool)$options['strict'] : true,
            'name' => !empty($options['name']) ? $options['name'] : 'structured_output'
        ];
        if (!empty($options['description'])) {
            $format['description'] = $options['description'];
        }

        $postFields = [
            'model' => $this->model,
            'input' => $input,
            'text' => [
                'format' => $format
            ]
        ];

        if (!empty($options['max_output_tokens'])) {
            $postFields['max_output_tokens'] = (int)$options['max_output_tokens'];
        }

        // Optionally add other OpenAI parameters here as needed

        $response = json_decode($this->sendCurlRequest($url, $headers, $postFields), true);

        return $response;
    }

    /**
     * Extract the structured output from a structured output response
     *
     * @param array $response The API response from createWithStructuredOutput
     * @return mixed The parsed structured output, or null if not found/refused
     */
    public function getStructuredOutput($response)
    {
        if (isset($response['output']) && count($response['output']) > 0) {
            foreach ($response['output'] as $item) {
                if ($item['type'] === 'message' && isset($item['content'][0])) {
                    $content = $item['content'][0];
                    if (isset($content['type']) && $content['type'] === 'output_text' && isset($content['text'])) {
                        // The model's output is a JSON string, decode it
                        $json = json_decode($content['text'], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            return $json;
                        }
                        // If not valid JSON, return as-is
                        return $content['text'];
                    } elseif (isset($content['type']) && $content['type'] === 'refusal') {
                        // Model refused to answer
                        return ['refusal' => $content['refusal']];
                    }
                }
            }
        }
        return null;
    }
}
