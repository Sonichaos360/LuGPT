<?php



// var_dump(json_decode(file_get_contents('../conversations/645756bcd4697.json')));
// exit;

require '../src/LuGPT/LuGPT.php';
$ENV = parse_ini_file('../.env');

//Initiate LuGPT
$luGPT = new Sonichaos360\LuGPT\LuGPT(
    $ENV['OPENAI_API_KEY'], //ApiKey
    'gpt-3.5-turbo', //model
    '1500', //tokens
    '1', //temp
    '../conversations', //conversationPath
    '../chat.log' //Log Path
);

// Create a new conversation
$conversationId = $luGPT->createConversation();

// Send messages within the conversation
$systemMessage = 'You are an assistant that translates between any languages.';
$userMessage1 = 'Translate the following sentence from English to Spanish: "Hello, how are you?"';
$response1 = $luGPT->Chat($systemMessage, $userMessage1, $conversationId);

var_dump($response1);


$userMessage2 = 'Now, please translate this: "I am fine, thank you."';
$response2 = $luGPT->Chat($systemMessage, $userMessage2, $conversationId);

var_dump($response2);

// echo $response2['choices'][0]['message']['content']." | ";