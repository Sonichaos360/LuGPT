<?php

require '../src/LuGPT/LuGPT.php';
$ENV = parse_ini_file('../.env');

//Initiate LuGPT
$LuGPT = new Sonichaos360\LuGPT\LuGPT(
    $ENV['OPENAI_API_KEY'], //ApiKey
    'text-davinci-003', //model
    '1500', //tokens
    '0.5', //temp
);

//Define system Message
$prompt = "This is a list of the best programming language ordered by creation date: ";

//Make a Chat Completion request
$result = $LuGPT->Completion($prompt);

var_dump($result);
