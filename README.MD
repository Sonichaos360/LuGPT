# LuGPT

A PHP library for interacting with OpenAI's Completions and ChatCompletions API.

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
  - [Initialization](#initialization)
  - [Chat](#chat)
  - [Completion](#completion)
  - [Conversations](#conversations)
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

use Sonichaos360\LuGPT\LuGPT;

$apiKey = 'your_openai_api_key';
$model = 'gpt-3.5-turbo';
$maxTokens = '1500';
$temp = '1';
$conversationsPath = '../conversations'; //Optional, necessary only if you will manage conversation history using createConversation()

$luGPT = new LuGPT($apiKey, $model, $maxTokens, $temp, $conversationsPath);

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