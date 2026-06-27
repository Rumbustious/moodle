/**
 * Rich editor integration for community posts.
 *
 * @module local_community/editor
 */
define(['jquery'], function($) {

    /**
     * Initialise editor container.
     * Moodle already initialises Atto/TinyMCE via PHP use_editor().
     * This function is kept for consistency but does nothing.
     */
    function init() {
        // No-op: Moodle handles editor init automatically.
    }

    /**
     * Get content from the editor (textarea synced by Moodle).
     *
     * @param {string} selector The CSS selector for the editor container.
     * @returns {string} HTML content from the editor.
     */
    function getContent(selector) {
        return $(selector).val();
    }

    return {
        init: init,
        getContent: getContent
    };
});
