<?php

/**
 * LuGPT - a PHP library for interacting with the OpenAI API.
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

class Completions
{
    protected $apiKey;
    protected $model;
    protected $tokens;
    protected $temperature;
    protected $conversationPath;
    protected $logPath;

    /**
     * Constructor for LuGpt class
     *
     * @param string $apiKey API key for OpenAI
     * @param string $model The model to use for OpenAI
     * @param int $tokens The number of tokens to use for OpenAI
     * @param float $temperature The temperature to use for OpenAI
     * @param string|null $conversationPath The path to store conversation history, null by default
     * @param string|null $logPath The path to store logs, null by default
     */
    public function __construct($apiKey, $model = 'gpt-3.5-turbo', $tokens = 1500, $temperature = 1, $conversationPath = null, $logPath = null)
    {
        if (!extension_loaded('curl')) {
            throw new \RuntimeException('cURL library is not available in this PHP installation.');
        }

        if (!isset($apiKey)) {
            throw new \InvalidArgumentException('API key is not set.');
        }

        $this->model = $model;
        $this->apiKey = $apiKey;
        $this->tokens = $tokens;
        $this->temperature = $temperature;
        $this->conversationPath = $conversationPath;
        $this->logPath = $logPath;
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

        $result = curl_exec($ch);

        if ($result === false) {
            throw new \RuntimeException('Curl error: ' . curl_error($ch));
        }

        curl_close($ch);

        //if isset $logPath then log the request using file_put_contents, appending and creathing the file if not exists
        if (isset($this->logPath)) {
            file_put_contents($this->logPath, date('Y-m-d H:i:s') . " \n" . $url . "REQUEST: \n" . json_encode($postFields) . "\n RESPONSE:" . $result, FILE_APPEND | LOCK_EX);
        }

        return $result;
    }

    /**
     * Chat completions
     *
     * @param string $systemMessage The system message to send to the API
     * @param string $userMessage The user message to send to the API
     * @param string|null $conversationId The conversation ID to use, null by default
     * @return mixed The API response
     */
    public function Chat($systemMessage, $userMessage, $conversationId = null)
    {
        //First Load the conversation file if exists
        $request_array[] = [
            'role' => 'system',
            // 'content' => $this->saveTokens($systemMessage)
            'content' => $systemMessage
        ];

        //If conversationId is set, load conversation file data in the middle of the array and concat messages
        if (isset($conversationId) && isset($this->conversationPath)) {
            $history = json_decode(file_get_contents($this->conversationPath . DIRECTORY_SEPARATOR . $conversationId . '.json'), true);
            foreach ($history as $message) {
                $request_array[] = [
                    'role' => $message['role'],
                    // 'content' => $this->saveTokens($message['content'])
                    'content' => $message['content']
                ];
            }
        }

        //Finally add the current user message
        $request_array[] = [
            'role' => 'user',
            // 'content' => $this->saveTokens($userMessage)
            'content' => $userMessage
        ];

        //Send the request
        $url = 'https://api.openai.com/v1/chat/completions';
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ];

        $postFields = [
            'model' => $this->model,
            'messages' => $request_array,
            'max_tokens' => intval($this->tokens),
            'temperature' => intval($this->temperature),
        ];

        $response = json_decode($this->sendCurlRequest($url, $headers, $postFields), true);

        //Update the conversation history by saving the last user message and response
        if (isset($conversationId) && isset($this->conversationPath) && isset($response['choices'][0]['message']['content'])) {
            $history[] = [
                'role' => 'user',
                'content' => $userMessage
            ];

            //assistant
            $history[] = [
                'role' => 'assistant',
                'content' => $response['choices'][0]['message']['content']
            ];

            file_put_contents($this->conversationPath . DIRECTORY_SEPARATOR . $conversationId . '.json', json_encode($history, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return $response;
    }

    /**
     * Save Tokens!
     * This function applies various methods in order to save tokens
     *
     * @param string $inputString The string to be cleaned
     * @return string The cleaned string
     */
    public function saveTokens($inputString)
    {
        // Remove line breaks and tabs
        $stringWithoutLineBreaks = preg_replace('/[\r\n\t]+/', ' ', $inputString);

        // Remove all spaces outside and inside HTML tags, but not in the tag definition
        $cleanedString = preg_replace_callback('/<[^>]*>|[^<>]+/', function ($matches) {
            if (substr($matches[0], 0, 1) === '<') {
                // Don't modify content inside tags
                return $matches[0];
            } else {
                // Remove all spaces outside and inside tags
                return preg_replace('/\s+/', '', $matches[0]);
            }
        }, $stringWithoutLineBreaks);

        // Remove all punctuation symbols (. , ! ¿ ¡ ? : ; `)
        $cleanedString = preg_replace('/[.,!¿¡?:;`]+/', '', $cleanedString);

        // Remove all articles (el, la, los, las, un, una, unos, unas), (the, a, an)
        $cleanedString = preg_replace('/\b(el|la|los|las|un|una|unos|unas|the|a|an)\b/', '', $cleanedString);

        // Replace full forms with abbreviations
        $abbreviations = [
            'please' => 'plz',
            'people' => 'ppl',
            'great' => 'gr8',
            'through' => 'thru',
            'tonight' => '2nite',
            'tomorrow' => '2morrow',
            'message' => 'msg',
            'later' => 'l8r',
            'between' => 'btwn',
            'because' => 'cuz',
            'your' => 'ur',
            'thanks' => 'thx',
            'see' => 'c',
            'be right back' => 'brb',
            'laughing out loud' => 'lol',
            'by the way' => 'btw'
        ];

        $cleanedString = preg_replace_callback('/\b(?:' . implode('|', array_keys($abbreviations)) . ')\b/', function ($matches) use ($abbreviations) {
            return $abbreviations[strtolower($matches[0])];
        }, $cleanedString);

        // Replace full forms with contractions without apostrophes
        $contractions = [
            'you are' => 'ure',
            'we are' => 'were',
            'they are' => 'theyre',
            'he is' => 'hes',
            'she is' => 'shes',
            'that is' => 'thats',
            'there is' => 'theres',
            'where is' => 'wheres',
            'how is' => 'hows',
            'will not' => 'wont',
            'have not' => 'havent',
            'has not' => 'hasnt',
            'had not' => 'hadnt',
            'could not' => 'couldnt',
            'should not' => 'shouldnt',
            'would not' => 'wouldnt',
            'did not' => 'didnt',
            'does not' => 'doesnt'
        ];

        $cleanedString = str_replace(array_keys($contractions), array_values($contractions), $cleanedString);

        return $cleanedString;
    }

    /**
     * Send a request to API - Single request, no chat
     *
     * @param string $prompt The prompt to send to the API
     * @return mixed The API response
     */
    public function Completion($prompt)
    {
        $url = 'https://api.openai.com/v1/completions';
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ];
        $postFields = [
            'model' => $this->model,
            'prompt' => $prompt,
            'max_tokens' => intval($this->tokens),
            'temperature' => intval($this->temperature),
        ];

        return json_decode($this->sendCurlRequest($url, $headers, $postFields), true);
    }

    /**
     * CreateConversation by creating JSON files in $this->conversationPath path
     * This allows us to preserve conversation history of a user or create different GPT agents with his own 'memory'
     *
     * @return string The conversation Id.
     */
    public function createConversation()
    {
        if (!isset($this->conversationPath) || !is_dir($this->conversationPath)) {
            throw new \InvalidArgumentException('Conversation path is not set or is not a valid path.');
        }

        //Unique conversation ID
        $conversationId = uniqid();

        //Create conversation json file with empty array
        $conversationFile = $this->conversationPath . DIRECTORY_SEPARATOR . $conversationId . '.json';

        //Create file, if succeed return conversationId otherwise throw exception
        if (file_put_contents($conversationFile, json_encode([])) === false) {
            throw new \RuntimeException('Could not create conversation file.');
        }

        return $conversationId;
    }

    /**
     * Preprocesses text content to prevent execution of PHP and script tags, remove HTML tags, and convert new lines to <br>.
     *
     * @param string $str The string to preprocess.
     * @return string The preprocessed string.
     */
    public function preparseContent($str)
    {
        return nl2br(strip_tags(str_replace(['<?php', '?>', '<?', '?>'], '', htmlspecialchars($str, ENT_NOQUOTES))));
    }

    /**
     * Preprocesses voice content to prevent execution of PHP and script tags
     * Remove everything except text inside markdown-like code blocks
     * Special utility for voice content output
     *
     * @param string $str The string to preprocess.
     * @return string The preprocessed string.
     */
    public function preparseVoice($str)
    {
        return preg_replace('/```(.*?)```/s', '', str_replace(['<?php', '?>', '<?', '?>'], '', $str));
    }
}
