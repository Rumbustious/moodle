<?php
namespace local_helpdesk;

defined('MOODLE_INTERNAL') || die();

class faq_service {

    public static function find_answer(string $question) {
        global $DB;

        $faqs = $DB->get_records('local_helpdesk_faq');

        foreach ($faqs as $faq) {
            if (stripos($question, $faq->question) !== false) {
                return $faq->answer;
            }

            if (!empty($faq->keywords)) {
                $keywords = explode(',', $faq->keywords);
                foreach ($keywords as $keyword) {
                    if (stripos($question, trim($keyword)) !== false) {
                        return $faq->answer;
                    }
                }
            }
        }

        return null;
    }
}