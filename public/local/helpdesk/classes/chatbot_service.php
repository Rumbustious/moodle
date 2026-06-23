<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Keyword-based chatbot service for local_helpdesk.
 *
 * @package    local_helpdesk
 * @copyright  2026 Helpdesk Plugin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_helpdesk;

defined('MOODLE_INTERNAL') || die();

/**
 * Simple customer-service chatbot.
 */
class chatbot_service {
    /**
     * Return an answer for known website/support topics.
     *
     * @param string $question
     * @return string|null
     */
    public static function answer_question(string $question): ?string {
    $q = \core_text::strtolower(trim($question));
    if ($q === '') {
        return null;
    }

    $faq = [
        ['keywords' => ['login', 'sign in', 'password', 'reset password'],
         'answer' => 'If you cannot log in, use "Forgotten your username or password?" on the login page. If you still cannot access your account, I can create a ticket for technical support.'],
        ['keywords' => ['enrol', 'enroll', 'course access', 'join course'],
         'answer' => 'To join a course, open the course catalog and select the course. If an enrolment key is required, ask your instructor for the key.'],
        ['keywords' => ['assignment', 'submit', 'upload'],
         'answer' => 'Open your course, go to the assignment activity, and click "Add submission". Upload your file and confirm submission before the deadline.'],
        ['keywords' => ['grade', 'result', 'marks'],
         'answer' => 'You can view your grades from your profile menu or by opening the course gradebook. Some grades appear only after teachers release them.'],
        ['keywords' => ['browser', 'cache', 'slow', 'not loading'],
         'answer' => 'Please try clearing your browser cache, then reload the page. If the issue remains, test in another browser and I can raise a ticket with your details.'],
        ['keywords' => ['mobile', 'app'],
         'answer' => 'You can use the Moodle mobile app by selecting this site URL and signing in with your normal account credentials.'],
        ['keywords' => ['contact', 'support', 'help'],
         'answer' => 'You are already in Helpdesk. Ask me your issue and if I cannot solve it, I will automatically create a ticket for technical support.'],
    ];

    foreach ($faq as $entry) {
        foreach ($entry['keywords'] as $keyword) {
            if (\core_text::strpos($q, $keyword) !== false) {
                return $entry['answer'];
            }
        }
    }

    return null;
}
}
