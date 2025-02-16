<?php
/**
 * LuGPT TTS - a PHP library for interacting with the OpenAI Text-to-Speech API.
 *
 * @package   Sonichaos360/LuGPT
 * @author    Luciano Joan Vergara
 * @license   MIT License (https://opensource.org/licenses/MIT)
 * @link      https://github.com/Sonichaos360/LuGPT
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
 * associated documentation files (the "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the
 * following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT
 * LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN
 * NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR
 * THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace Sonichaos360\LuGPT;

class Tts
{
    protected $apiKey;
    protected $model;
    protected $voice;
    protected $responseFormat;
    protected $speed;
    protected $logPath;
    protected $storagePath;

    /**
     * Constructor for the Tts class.
     *
     * @param string      $apiKey         Your OpenAI API key.
     * @param string      $model          TTS model to use ("tts-1" or "tts-1-hd"). Default is "tts-1".
     * @param string      $voice          The voice to use (supported: alloy, ash, coral, echo, fable, onyx, nova, sage, shimmer). Default is "alloy".
     * @param string      $responseFormat The desired output format ("mp3" by default; other supported formats: opus, aac, flac, wav, pcm).
     * @param float       $speed          The speed of the generated audio (range: 0.25 to 4.0; default is 1).
     * @param string|null $logPath        Optional path to a log file.
     * @param string|null $storagePath    Optional default path where generated audio files will be stored.
     *
     * @throws \RuntimeException if the cURL extension is not available.
     * @throws \InvalidArgumentException if the API key is not set.
     */
    public function __construct($apiKey, $model = 'tts-1', $voice = 'alloy', $responseFormat = 'mp3', $speed = 1.0, $logPath = null, $storagePath = null)
    {
        if (!extension_loaded('curl')) {
            throw new \RuntimeException('cURL library is not available in this PHP installation.');
        }

        if (empty($apiKey)) {
            throw new \InvalidArgumentException('API key is not set.');
        }

        $this->apiKey         = $apiKey;
        $this->model          = $model;
        $this->voice          = $voice;
        $this->responseFormat = $responseFormat;
        $this->speed          = $speed;
        $this->logPath        = $logPath;
        $this->storagePath    = $storagePath;
    }

    /**
     * Send a cURL request to the TTS endpoint.
     *
     * @param string $url        The request URL.
     * @param array  $headers    Request headers.
     * @param array  $postFields POST fields.
     *
     * @return string The raw binary response (audio data).
     *
     * @throws \RuntimeException if a cURL error occurs.
     */
    public function sendCurlRequest($url, $headers, $postFields)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postFields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        if ($result === false) {
            throw new \RuntimeException('Curl error: ' . curl_error($ch));
        }

        curl_close($ch);

        // Log the request and a summary of the response (avoid logging raw binary data)
        if (isset($this->logPath)) {
            $logEntry = sprintf(
                "[%s] URL: %s\nREQUEST: %s\nRESPONSE: %d bytes returned\n\n",
                date('Y-m-d H:i:s'),
                $url,
                json_encode($postFields),
                strlen($result)
            );
            file_put_contents($this->logPath, $logEntry, FILE_APPEND | LOCK_EX);
        }

        return $result;
    }

    /**
     * Synthesize speech from text using the OpenAI TTS API.
     *
     * @param string $text The text to be converted to speech (max 4096 characters).
     *
     * @return string The binary audio data.
     */
    public function synthesize($text)
    {
        $url = 'https://api.openai.com/v1/audio/speech';
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ];

        $postFields = [
            'model'           => $this->model,
            'input'           => $text,
            'voice'           => $this->voice,
            'response_format' => $this->responseFormat,
            'speed'           => $this->speed,
        ];

        return $this->sendCurlRequest($url, $headers, $postFields);
    }

    /**
     * Synthesize speech from text and write the audio to a specified file.
     *
     * @param string $text     The text to be converted to speech.
     * @param string $filePath The file path (including file name) where the audio will be saved.
     *
     * @return bool True on success, false otherwise.
     */
    public function synthesizeToFile($text, $filePath)
    {
        $audioData = $this->synthesize($text);
        if (file_put_contents($filePath, $audioData) === false) {
            return false;
        }
        return true;
    }

    /**
     * Convenience method to synthesize speech and automatically store the audio file
     * in the assigned storage path. If a file name is not provided, a unique name is generated.
     *
     * @param string      $text     The text to be converted to speech.
     * @param string|null $fileName Optional file name (with extension). If omitted, a unique name is generated.
     *
     * @return string The full path to the saved audio file.
     *
     * @throws \InvalidArgumentException if no valid storage path is set.
     */
    public function synthesizeAndStore($text, $fileName = null)
    {
        if (empty($this->storagePath) || !is_dir($this->storagePath)) {
            throw new \InvalidArgumentException("Storage path is not set or is not a valid directory.");
        }
        if ($fileName === null) {
            $fileName = uniqid('speech_') . '.' . $this->responseFormat;
        }
        $fullPath = rtrim($this->storagePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;
        return $this->synthesizeToFile($text, $fullPath) ? $fullPath : false;
    }
}
