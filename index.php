<?php
require(__DIR__ . "/utils.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondWithError('Invalid Request Method', 'Use POST');
}

$data = $_POST;
logData('event.log', "Received POST Data: " . print_r($data, true));

$eventHandlers = [
    'ONCRMLEADADD' => 'handleLeadEvent',
    'ONCRMDEALADD' => 'handleDealEvent'
];

$event_type = $data['event'] ?? null;
if (isset($eventHandlers[$event_type])) {
    call_user_func($eventHandlers[$event_type], $data);
} else {
    respondWithError('Unsupported Event', "Event '$event_type' is not supported.");
}

exit;
