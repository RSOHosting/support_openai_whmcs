<?php
/**
 * Hook for WHMCS to respond to tickets using OpenAI
 */


require_once __DIR__ . '/vendor/autoload.php';

use Orhanerday\OpenAi\OpenAi;

if (!defined('WHMCS'))
    die('You cannot access this file directly.');

function new_ticket_opened($vars) {
    $open_ai_key = getenv('OPENAI_API_KEY');
    $open_ai = new OpenAi($open_ai_key);
    $ticket_subject = $vars['subject'];
    $ticket_message = $vars['message'];

    $prompt = "$ticket_subject \r\n $ticket_message";

    $complete = $open_ai->completion([
        'model' => 'davinci',
        'prompt' => $prompt,
        'temperature' => 0.9,
        'max_tokens' => 150,
        'frequency_penalty' => 0,
        'presence_penalty' => 0.6,
    ]);

    $response = json_decode($complete, true);
    $response_text = $response['choices'][0]['text'];

    // Send the response to the ticket
    $ticket_id = $vars['ticketid'];
    localAPI('AddTicketReply', [
        'ticketid' => $ticket_id,
        'message' => $response_text,
        'adminusername' => "RSO Support",
    ]);
}

add_hook('TicketUserReply', 1, 'new_ticket_opened');
