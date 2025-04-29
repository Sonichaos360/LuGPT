# LuGPT

A PHP library for interacting with OpenAI's Completions and ChatCompletions API.

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
  - [Initialization](#initialization)
  - [Chat](#chat)
  - [Completion](#completion)
  - [Conversations](#conversations)
  - [Web Search](#web-search)
- [Todo](#todo)
- [Issues](#issues)
- [Contributing](#contributing)
- [License](#license)

## Installation

Install the library using [Composer](https://getcomposer.org/):

```bash
composer require sonichaos360/lugpt
```

## Usage

### Initialization

Initialize the `LuGPT` class with your OpenAI API key:

```php
require_once 'vendor/autoload.php';

use Sonichaos360\LuGPT\Completions;

$apiKey = 'your_openai_api_key';
$model = 'gpt-3.5-turbo';
$maxTokens = '1500';
$temp = '1';
$conversationsPath = '../conversations'; //Optional, necessary only if you will manage conversation history using createConversation()

$luGPT = new Completions($apiKey, $model, $maxTokens, $temp, $conversationsPath);

```

In this example, we are using the `gpt-3.5-turbo` model, which is optimized for chat. The chat-optimized models can be used with the `chat()` method. If you want to use the `completion()` method, you will need to use `text-davinci-003` or another non-chat-optimized model. A list of all available OpenAI models can be found [here](https://platform.openai.com/docs/models).


### Chat

Send a message using Chat Completions API:

```php
$systemMessage = 'You are an assistant that translates English to French.';
$userMessage = 'Translate the following sentence: "Hello, how are you?"';
$response = $luGPT->Chat($systemMessage, $userMessage);

echo $response['choices'][0]['message']['content'];
```

### Conversations

Create and manage conversations:

```php
// Create a new conversation
$conversationId = $luGPT->createConversation();

// Send messages within the conversation
$systemMessage = 'You are an assistant that translates between any languages.';
$userMessage1 = 'Translate the following sentence from English to French: "Hello, how are you?"';
$response1 = $luGPT->Chat($systemMessage, $userMessage1, $conversationId);

echo $response1['choices'][0]['message']['content']." | ";

$userMessage2 = 'Now, please translate this: "I am fine, thank you."';
$response2 = $luGPT->Chat($systemMessage, $userMessage2, $conversationId);

echo $response2['choices'][0]['message']['content']." | ";
```

This example uses a `conversationId`, which allows the API to remember the context of previous messages and responses. The conversations will be created as separate JSON files stored in the `$conversationsPath` directory.


### Completion

Send a prompt to the `text-davinci-003` or other non chat completions optimized model:

```php
$prompt = 'Translate the following English sentence to French: "Hello, how are you?"';
$response = $luGPT->Completion($prompt);

echo $response["choices"][0]["text"];
```

### Web Search

Use OpenAI's web search tool to retrieve the latest information from the internet:

```php
use Sonichaos360\LuGPT\Responses;

$apiKey = 'your_openai_api_key';
$model = 'gpt-4o'; // Recommended model for web search
$luResponses = new Responses($apiKey, $model);

// Basic web search
$input = "What was a positive news story from today?";
$response = $luResponses->createWithWebSearch($input);
echo $luResponses->getOutputText($response);

// Web search with user location
$input = "What are the best restaurants around me?";
$options = [
    'userLocation' => [
        'country' => 'US',
        'city' => 'New York',
        'region' => 'New York',
        'timezone' => 'America/New_York'
    ],
    'searchContextSize' => 'medium', // Options: low, medium, high
    'forceWebSearch' => true // Force the model to use web search
];

$response = $luResponses->createWithWebSearch($input, $options);
echo $luResponses->getOutputText($response);

// Get citations
$citations = $luResponses->getCitations($response);
foreach ($citations as $citation) {
    if ($citation['type'] === 'url_citation') {
        echo "Source: " . $citation['title'] . " - " . $citation['url'] . "\n";
    }
}
```

The web search functionality allows the model to search the internet for the latest information before generating a response. You can customize:

- **User Location**: Provide geographic context to get more relevant search results.
- **Search Context Size**: Control how much context is retrieved from the web:
  - `high`: Most comprehensive, highest cost, slower response
  - `medium`: Balanced context, cost, and latency (default)
  - `low`: Least context, lowest cost, fastest response
- **Force Web Search**: Ensure the model uses web search by setting `forceWebSearch` to `true`

Web search responses include citations to source URLs that can be extracted using the `getCitations()` method.

### Structured Outputs (JSON Schema)

You can ensure the model's response adheres to a specific JSON Schema using the `createWithStructuredOutput` method in the `Responses` class.

**Example: Extracting a person's name and age from a sentence**

```php
use Sonichaos360\LuGPT\Responses;

$apiKey = 'your_openai_api_key';
$responses = new Responses($apiKey, 'gpt-4o-2024-08-06');

// Define your schema
$schema = [
    "type" => "object",
    "properties" => [
        "name" => ["type" => "string"],
        "age" => ["type" => "integer"]
    ],
    "required" => ["name", "age"],
    "additionalProperties" => false
];

// Prepare input messages
$input = [
    ["role" => "system", "content" => "Extract the person's name and age from the user's message."],
    ["role" => "user", "content" => "My name is Alice and I am 30 years old."]
];

// Call the API
$response = $responses->createWithStructuredOutput($input, $schema, [
    'name' => 'person_info'
]);

// Extract the structured output
$structured = $responses->getStructuredOutput($response);

print_r($structured);
```

This will return an array matching your schema, for example:

```php
Array
(
    [name] => Alice
    [age] => 30
)
```

## TODO

Here are some upcoming features and improvements we have planned for the LuGPT library. We encourage contributors to take on these tasks or suggest new ones by creating issues on GitHub:

1. Refactor the `saveTokens()` function, which was deactivated due to strange behavior with non-English language characters.
2. Improve error handling and provide more informative error messages.
3. Implement unit tests for the library to ensure code quality and functionality.

Please consider contributing to the development of these features or suggesting new ones by creating an issue or submitting a pull request. Your input and collaboration are greatly appreciated.

## Issues

If you encounter any problems or have suggestions for improvements, we welcome you to create an issue on GitHub. To do so, please follow these steps:

1. Navigate to the [Issues](https://github.com/sonichaos360/lugpt/issues) section of the LuGPT repository.
2. Click on the "New issue" button.
3. Provide a clear and descriptive title for the issue.
4. In the description, include as much detail as possible, such as:
   - A summary of the problem or suggestion.
   - Steps to reproduce the issue, if applicable.
   - The expected behavior and the actual behavior.
   - Any error messages or logs, if available.
   - The PHP version and operating system you are using.
   - Attach any relevant screenshots or code snippets, if necessary.

When creating an issue, please keep the following recommendations in mind:

- Make sure to search the existing issues before submitting a new one, to avoid duplicates.
- Be respectful and courteous to other users and maintain a constructive discussion.
- Stay on topic and keep the conversation relevant to the issue at hand.

## Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository.
2. Create a new branch for your changes.
3. Make your changes.
4. Submit a pull request.

## License

This library is released under the [MIT License](LICENSE).