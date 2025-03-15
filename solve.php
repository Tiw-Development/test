<?php
header('Content-Type: application/json');
if (!isset($_GET["apikey"]) || !isset($_GET["sitekey"]) || !isset($_GET["pageurl"])) {
    echo json_encode(["error" => "Missing parameters"]);
    exit;
}

$apikey = $_GET["apikey"];
$sitekey = $_GET["sitekey"];
$pageurl = $_GET["pageurl"];

// إرسال طلب إلى 2Captcha
$createTask = file_get_contents("https://api.2captcha.com/createTask", false, stream_context_create([
    "http" => [
        "method" => "POST",
        "header" => "Content-Type: application/json",
        "content" => json_encode([
            "clientKey" => $apikey,
            "task" => [
                "type" => "RecaptchaV2TaskProxyless",
                "websiteKey" => $sitekey,
                "websiteURL" => $pageurl
            ]
        ])
    ]
]));

$taskData = json_decode($createTask, true);
if ($taskData["errorId"] !== 0) {
    echo json_encode(["error" => "Failed to create task", "details" => $taskData]);
    exit;
}

$taskId = $taskData["taskId"];

// انتظار الحل
for ($i = 0; $i < 30; $i++) {
    sleep(10);

    $getResult = file_get_contents("https://api.2captcha.com/getTaskResult", false, stream_context_create([
        "http" => [
            "method" => "POST",
            "header" => "Content-Type: application/json",
            "content" => json_encode(["clientKey" => $apikey, "taskId" => $taskId])
        ]
    ]));

    $resultData = json_decode($getResult, true);
    if ($resultData["status"] === "ready") {
        echo json_encode(["success" => true, "gRecaptchaResponse" => $resultData["solution"]["gRecaptchaResponse"]]);
        exit;
    }
}

echo json_encode(["error" => "CAPTCHA not solved"]);
?>
