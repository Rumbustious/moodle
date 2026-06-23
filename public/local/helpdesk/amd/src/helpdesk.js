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
 * AMD JavaScript module for local_helpdesk.
 * Handles toast messages, chat widget, unread badge polling,
 * status changes, feedback stars, and chat open/close actions.
 *
 * @module     local_helpdesk/helpdesk
 * @copyright  2026 Helpdesk Plugin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import {getString} from 'core/str';

/** Polling interval in milliseconds for unread count & chat messages. */
const POLL_INTERVAL = 5000;

/** Track last message timestamp for incremental fetches. */
let lastMessageTime = 0;

/** Current chat id, if a chat widget is active on this page. */
let activeChatId = 0;

/** Polling timer reference. */
let pollTimer = null;

/** Flag to prevent concurrent message fetches (prevents double messages). */
let isFetching = false;

// ---------------------------------------------------------------------------
// Toast helper
// ---------------------------------------------------------------------------

/**
 * Show a toast / notification message.
 *
 * @param {string} message
 * @param {string} type  success | warning | error | info
 */
const showToast = (message, type = 'success') => {
    Notification.addNotification({
        message,
        type,
    });
};

// ---------------------------------------------------------------------------
// Unread badge poller
// ---------------------------------------------------------------------------

/**
 * Poll the server for unread helpdesk chat messages and update the badge.
 */
const pollUnread = () => {
    Ajax.call([{
        methodname: 'local_helpdesk_get_unread_count',
        args: {},
    }])[0].then((result) => {
        const fab   = document.getElementById('helpdesk-chat-fab');
        const badge = document.getElementById('helpdesk-unread-badge');
        if (!fab || !badge) {
            return;
        }
        if (result.count > 0) {
            badge.textContent = result.count;
            fab.classList.remove('d-none');
        } else {
            fab.classList.add('d-none');
            badge.textContent = '';
        }

        const fabBtn = document.getElementById('helpdesk-chat-fab-btn');
        if (fabBtn && result.ticketid > 0) {
            fabBtn.onclick = () => {
                window.location.href = M.cfg.wwwroot + '/local/helpdesk/view.php?id=' + result.ticketid;
            };
        }
        return result;
    }).catch(() => {
        // Silently ignore polling errors.
    });
};

/**
 * Start the unread count polling loop.
 * @export
 */
export const initUnreadPoller = () => {
    pollUnread();
    pollTimer = setInterval(pollUnread, POLL_INTERVAL);
};

// ---------------------------------------------------------------------------
// Chat widget
// ---------------------------------------------------------------------------

/**
 * Append a message bubble to the chat message list.
 *
 * @param {HTMLElement} container The container to append to.
 * @param {Object} msg The message data object.
 */
const appendMessage = (container, msg) => {
    const wrapper = document.createElement('div');
    wrapper.className = 'mb-2 ' + (msg.ismine ? 'msg-mine text-right' : 'msg-other');

    const bubble = document.createElement('span');
    bubble.className = 'bubble';
    bubble.textContent = msg.message;

    const meta = document.createElement('div');
    meta.className = 'msg-meta';
    meta.textContent = msg.fullname + ' · ' + new Date(msg.timecreated * 1000).toLocaleTimeString();

    wrapper.appendChild(bubble);
    wrapper.appendChild(meta);
    container.appendChild(wrapper);
};

/**
 * Fetch new messages for the active chat and append them to the container.
 *
 * @param {HTMLElement} container The chat messages container.
 * @param {number} chatId The active chat ID.
 */
const fetchMessages = (container, chatId) => {
    // Prevent duplicate calls from overlapping.
    if (isFetching) {
        return Promise.resolve();
    }
    isFetching = true;

    return Ajax.call([{
        methodname: 'local_helpdesk_get_messages',
        args: {chatid: chatId, since: lastMessageTime},
    }])[0].then((result) => {
        isFetching = false;

        if (result.chatstatus === 'closed') {
            clearInterval(pollTimer);
            getString('chatclosed', 'local_helpdesk').then((str) => {
                const notice = document.createElement('p');
                notice.className = 'text-muted text-center mt-2';
                notice.textContent = str;
                container.appendChild(notice);
                return str;
            }).catch(() => {
                // Ignore.
            });
        }

        result.messages.forEach((msg) => {
            // Check timestamp to ensure the message isn't already rendered.
            if (msg.timecreated > lastMessageTime) {
                appendMessage(container, msg);
                lastMessageTime = msg.timecreated;
            }
        });

        if (result.messages.length) {
            container.scrollTop = container.scrollHeight;
        }
        return result;
    }).catch((error) => {
        isFetching = false;
        throw error;
    });
};

/**
 * Initialise the chat widget on a ticket view page.
 *
 * @param {Object} options Configuration options.
 * @export
 */
export const initChatWidget = (options) => {
    const {ticketid, chatid, issupport} = options;
    activeChatId = chatid;

    const msgContainer = document.getElementById('helpdesk-chat-messages');

    if (activeChatId && msgContainer) {
        fetchMessages(msgContainer, activeChatId);
        pollTimer = setInterval(() => fetchMessages(msgContainer, activeChatId), POLL_INTERVAL);
    }

    pollUnread();
    setInterval(pollUnread, POLL_INTERVAL);

    // --- Send message ---
    const sendBtn = document.getElementById('helpdesk-chat-send');
    const chatInput = document.getElementById('helpdesk-chat-input');

    if (sendBtn && chatInput && msgContainer) {
        const doSend = () => {
            const text = chatInput.value.trim();
            if (!text || sendBtn.disabled) {
                return;
            }

            // Disable UI to prevent double-clicks.
            sendBtn.disabled = true;
            chatInput.readOnly = true;

            Ajax.call([{
                methodname: 'local_helpdesk_send_message',
                args: {chatid: activeChatId, message: text},
            }])[0].then(() => {
                chatInput.value = '';
                chatInput.readOnly = false;
                sendBtn.disabled = false;
                chatInput.focus();

                // Fetch immediately after sending.
                return fetchMessages(msgContainer, activeChatId);
            }).catch((e) => {
                sendBtn.disabled = false;
                chatInput.readOnly = false;
                Notification.exception(e);
            });
        };

        sendBtn.addEventListener('click', (e) => {
            e.preventDefault();
            doSend();
        });

        chatInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                doSend();
            }
        });
    }

    // --- Status change (support) ---
    const statusBtn = document.getElementById('helpdesk-status-btn');
    const statusSelect = document.getElementById('helpdesk-status-select');
    const statusMsg = document.getElementById('helpdesk-status-msg');
    if (statusBtn && statusSelect && issupport) {
        statusBtn.addEventListener('click', () => {
            const newStatus = statusSelect.value;
            Ajax.call([{
                methodname: 'local_helpdesk_change_ticket_status',
                args: {ticketid, status: newStatus},
            }])[0].then((result) => {
                if (result.success && statusMsg) {
                    getString('statuschanged', 'local_helpdesk', newStatus).then((str) => {
                        statusMsg.innerHTML = '<div class="alert alert-success">' + str + '</div>';
                        return str;
                    }).catch(() => {
                        // Ignore.
                    });
                }
                return result;
            }).catch(Notification.exception);
        });
    }

    // --- Open/Close Chat ---
    const openChatBtn = document.getElementById('helpdesk-open-chat-btn');
    if (openChatBtn && issupport) {
        openChatBtn.addEventListener('click', () => {
            Ajax.call([{methodname: 'local_helpdesk_open_chat', args: {ticketid}}])[0]
                .then((result) => {
                    activeChatId = result.chatid;
                    window.location.reload();
                    return result;
                }).catch(Notification.exception);
        });
    }

    const closeChatBtn = document.getElementById('helpdesk-close-chat-btn');
    if (closeChatBtn && issupport) {
        closeChatBtn.addEventListener('click', () => {
            Ajax.call([{methodname: 'local_helpdesk_close_chat', args: {chatid: activeChatId}}])[0]
                .then((result) => {
                    window.location.reload();
                    return result;
                }).catch(Notification.exception);
        });
    }

    // --- Star Ratings ---
    const stars = document.querySelectorAll('.helpdesk-star');
    const ratingInput = document.getElementById('helpdesk-feedback-rating');
    if (stars.length && ratingInput) {
        stars.forEach((star) => {
            star.addEventListener('click', () => {
                const val = parseInt(star.dataset.value, 10);
                ratingInput.value = val;
                stars.forEach((s) => s.classList.toggle('active', parseInt(s.dataset.value, 10) <= val));
            });
            star.addEventListener('mouseover', () => {
                const val = parseInt(star.dataset.value, 10);
                stars.forEach((s) => s.classList.toggle('active', parseInt(s.dataset.value, 10) <= val));
            });
            star.addEventListener('mouseout', () => {
                const current = parseInt(ratingInput.value, 10);
                stars.forEach((s) => s.classList.toggle('active', parseInt(s.dataset.value, 10) <= current));
            });
        });
    }

    // --- Submit Feedback ---
    const feedbackBtn = document.getElementById('helpdesk-feedback-submit');
    const feedbackMsg = document.getElementById('helpdesk-feedback-msg');
    if (feedbackBtn && ratingInput) {
        feedbackBtn.addEventListener('click', () => {
            const rating  = parseInt(ratingInput.value, 10);
            const comment = document.getElementById('helpdesk-feedback-comment')?.value || '';
            if (!rating) {
                getString('feedbackrating', 'local_helpdesk').then((str) => showToast(str, 'warning')).catch(() => {
                    // Ignore.
                });
                return;
            }
            Ajax.call([{
                methodname: 'local_helpdesk_submit_feedback',
                args: {ticketid, rating, comment},
            }])[0].then((result) => {
                if (result.success) {
                    getString('feedbacksubmitted', 'local_helpdesk').then((str) => {
                        showToast(str, 'success');
                        if (feedbackMsg) {
                            feedbackMsg.innerHTML = '<div class="alert alert-success">' + str + '</div>';
                        }
                        const card = document.getElementById('helpdesk-feedback-card');
                        if (card) {
                            card.querySelector('button')?.setAttribute('disabled', 'disabled');
                        }
                        return str;
                    }).catch(() => {
                        // Ignore.
                    });
                }
                return result;
            }).catch(Notification.exception);
        });
    }
};