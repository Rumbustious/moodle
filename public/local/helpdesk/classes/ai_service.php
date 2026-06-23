<?php
namespace local_helpdesk;

defined('MOODLE_INTERNAL') || die();

class ai_service {

    public static function ask_llm(string $question) {

        $endpoint = "http://127.0.0.1:8000/helpdesk-ai";

        $payload = json_encode([
            "question" => $question
        ]);

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        $result = curl_exec($ch);
        curl_close($ch);

        if (!$result) {
            return null;
        }

        return json_decode($result, true);
    }
}