<?php
// api.php - مرتبط بملف الحماية
// -------------------------------------------------------

// 🔥 استدعاء ملف الحماية (أول شيء يجب تنفيذه)
require_once __DIR__ . '/firewall.php';

// (إذا وصل الكود هنا، فهذا يعني أن ملف الحماية سمح بالمرور)
// -------------------------------------------------------

ob_start();
error_reporting(0);
ini_set('display_errors', 0);
ini_set('memory_limit', '1024M'); 
set_time_limit(180);

// ******************************************************
// إعدادات Gemini API
// ******************************************************
define('GEMINI_API_KEY', 'AIzaSyBi0_DSENtqXFqcr4OA7tfj02QqrS9ZNZ4'); 
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models');

function sendErrorResponse($http_code, $message) {
    ob_clean();
    http_response_code($http_code);
    echo json_encode(['error' => ['message' => $message]]);
    exit;
}

$input_json = file_get_contents('php://input');
$input_data = json_decode($input_json, true);

if (!$input_data) sendErrorResponse(400, 'Invalid JSON payload.');

$frontend_model_id = $input_data['model'] ?? 'flash-lite'; 
$contents = $input_data['contents'] ?? [];

if (empty($contents)) sendErrorResponse(400, 'No contents provided.');

$model_map = [
    'flash-lite' => 'gemini-2.5-flash-lite', 
    'flash'      => 'gemini-2.5-flash',
    'pro'        => 'gemini-3-flash-preview',
];

$is_research_mode = ($frontend_model_id === 'gemini-pro-research');
$final_model_id = $is_research_mode ? 'gemini-2.5-flash' : ($model_map[$frontend_model_id] ?? 'gemini-2.5-flash');

// -------------------------------------------------------
// 💡 إعدادات الهوية (System Instruction)
// -------------------------------------------------------
// التعليمات: الاسم Converai، المطور Chat AI Community، ووصف متغير.
$system_instruction_text = "أنت Converai. تم تطويرك وصناعتك بواسطة Chat AI Community. عند سؤالك عن هويتك أو من صنعك، أجب دائمًا أنك Converai وأن مطورك هو Chat AI Community. عند تقديم وصف لنفسك، استخدم صياغة متغيرة ومبتكرة في كل مرة لكي لا تبدو الردود آلية أو مكررة، مع الحفاظ على اسمك وهويتك المصنعية.";

$request_payload = [
    'contents' => $contents,
    'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 8192],
    'system_instruction' => [
        'parts' => [
            ['text' => $system_instruction_text]
        ]
    ]
];

if ($is_research_mode) $request_payload['tools'] = [['googleSearch' => (object)[]]];

$ch = curl_init(GEMINI_API_URL . "/{$final_model_id}:generateContent?key=" . GEMINI_API_KEY);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code >= 400) {
    $err = json_decode($response, true)['error']['message'] ?? "API Error";
    sendErrorResponse($http_code, $err);
}

ob_clean();
echo $response;
?>