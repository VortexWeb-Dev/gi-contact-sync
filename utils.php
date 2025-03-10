<?php
require(__DIR__ . "/crest/crest.php");

date_default_timezone_set('Asia/Dubai');

// function to log data
function logData($logfile, $data)
{
    date_default_timezone_set('Asia/Kolkata');

    $logFile = __DIR__ . '/logs/' . $logfile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - $data\n", FILE_APPEND);
}

// function to get lead
function getLead($leadId)
{
    $response = CRest::call("crm.lead.get", ["ID" => $leadId]);
    return $response["result"];
}

// function to get deal
function getDeal($dealId)
{
    $response = CRest::call("crm.deal.get", ["ID" => $dealId]);
    return $response["result"];
}

// function to update lead
function updateLead($leadId, $leadData)
{
    $response = CRest::call('crm.lead.update', [
        'ID' => $leadId,
        'fields' => $leadData
    ]);

    return $response['result'];
}

// function to update deal
function updateDeal($dealId, $dealData)
{
    $response = CRest::call('crm.deal.update', [
        'ID' => $dealId,
        'fields' => $dealData
    ]);

    return $response['result'];
}

// event handlers
function handleLeadEvent($data)
{
    $lead_id = $data['data']['FIELDS']['ID'] ?? null;
    if (!$lead_id) {
        respondWithError('Missing Lead ID');
    }

    processEntity($lead_id, 'lead', 'lead.log');
}

// event handlers
function handleDealEvent($data)
{
    $deal_id = $data['data']['FIELDS']['ID'] ?? null;
    if (!$deal_id) {
        respondWithError('Missing Deal ID');
    }

    processEntity($deal_id, 'deal', 'deal.log');
}

// function to process entity
function processEntity($id, $type, $logFile)
{
    try {
        $entity = ($type === 'lead') ? getLead($id) : getDeal($id);
        logData($logFile, "Fetched " . ucfirst($type) . " Data: " . print_r($entity, true));

        if (!$entity) {
            respondWithError(ucfirst($type) . ' Data Missing', "$type ID: $id");
        }

        if (isset($entity['CONTACT_ID'])) {
            return;
        }

        $fieldMapping = [
            'lead' => [
                'NAME' => 'UF_CRM_1741123034066',
                'EMAIL' => 'UF_CRM_1741127499',
                'PHONE' => 'UF_CRM_1741126758',
                'CUSTOM_FIELD' => 'UF_CRM_1654582309'
            ],
            'deal' => [
                'NAME' => 'UF_CRM_1721198189214',
                'EMAIL' => 'UF_CRM_1721198325274',
                'PHONE' => 'UF_CRM_1736406984',
                'CUSTOM_FIELD' => 'UF_CRM_62A5B8743F62A'
            ]
        ];

        $contactData = [
            'NAME' => $entity[$fieldMapping[$type]['NAME']] ?? '',
            'EMAIL' => [['VALUE_TYPE' => 'WORK', 'VALUE' => $entity[$fieldMapping[$type]['EMAIL']] ?? '']],
            'PHONE' => [['VALUE_TYPE' => 'WORK', 'VALUE' => $entity[$fieldMapping[$type]['PHONE']] ?? '']],
            'UF_CRM_637B1AE74AC23' => $entity[$fieldMapping[$type]['CUSTOM_FIELD']] ?? ''
        ];

        logData('contact.log', "Contact Data: " . print_r($contactData, true));

        $contactId = createContact($contactData);
        if (!$contactId) {
            respondWithError('Contact Creation Error', 'Failed to create contact');
        }

        $updateData = ['CONTACT_ID' => $contactId];
        $response = ($type === 'lead') ? updateLead($id, $updateData) : updateDeal($id, $updateData);

        logData('update.log', "Updated " . ucfirst($type) . " Data: " . print_r($response, true));
    } catch (Exception $e) {
        respondWithError(ucfirst($type) . ' Fetch Error', $e->getMessage());
    }
}

// function to respond with error
function respondWithError($error, $details = '')
{
    logData('error.log', "$error: $details");
    echo json_encode(['error' => $error, 'details' => $details]);
    exit;
}

// function to create contact
function createContact($contactData)
{
    $response = CRest::call('crm.contact.add', ['fields' => $contactData]);
    return $response['result'];
}
