/**
 * Post details page logic for Moodle 5.1.
 *
 * @module      local_community/post
 * @copyright   2026 Youssef Khaled
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/notification'], function($, Notification) {

    let currentPostId = null;
    const SELECTORS = {
        container: '#post-data-container',
        answerInput: '#answercontent',
        submitBtn: '#addanswer'
    };

    /**
     * Convert a timestamp to a human-readable "time ago" format.
     *
     * @param {Number} timestamp The timestamp in seconds.
     * @returns {String} A string like "5 minutes ago".
     */
    function timeAgo(timestamp) {
    const now = new Date();
    const time = new Date(timestamp * 1000); // PHP timestamp in seconds
    const diff = Math.floor((now - time) / 1000); // difference in seconds

    if (diff < 60){ return `${diff} seconds ago`;}
    if (diff < 3600) {return `${Math.floor(diff / 60)} minutes ago` ;}
    if (diff < 86400) {return `${Math.floor(diff / 3600)} hours ago`;}
    if (diff < 2592000) { return `${Math.floor(diff / 86400)} days ago`;}

    return time.toLocaleDateString(); // fallback: full date
}

    /**
     * Render the post details and answers into the container.
     *
     * @param {Object} data The post and answers data.
     */
    function render(data) {
        const {post, answers} = data;

        // Post Header and Content.
        let html = `
            <div class="post-container mb-5 animate__animated animate__fadeIn">
                <div class="border-bottom pb-3 mb-4">
                    <h1 class="display-6 fw-bold text-dark mb-2">${post.title}</h1>
                    <div class="d-flex align-items-center flex-wrap gap-3 text-muted small">
                    <div class="me-3">
        <i class="fa fa-calendar-o me-1"></i> Asked ${timeAgo(post.timecreated)}
    </div>
                        <div><i class="fa fa-eye me-1"></i> Viewed ${post.views || 0} times</div>
                        <div class="badge bg-light text-dark border">
                            <i class="fa fa-star text-warning me-1"></i>${post.votes} Score
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 mt-2">
                        <div class="post-body fs-5 text-dark mb-4">${post.content}</div>
                        <div class="d-flex justify-content-end">
                            <div class="user-info-card p-3 rounded bg-light border-start border-primary border-4"
                                 style="min-width: 180px;">
                                <span class="text-muted d-block small mb-2">Asked by</span>
                                <div class="author-info">
                                    <div class="fw-bold text-primary mb-1">
                                        ${post.firstname} ${post.lastname}
                                    </div>
                                    <div class="small text-muted mb-1">
                                        <strong>${post.reputation}</strong> reputation
                                    </div>
                                    <div class="d-flex flex-wrap gap-1">
                                        ${post.badges.map(b => `
                                            <span class="badge bg-secondary" style="font-size: 0.7rem;">
                                                ${b.name}
                                            </span>`).join('')}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="answers-header d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0">${answers.length} Answers</h4>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button">
                        Sort by: Votes
                    </button>
                </div>
            </div>`;

        // Answers List.
        answers.forEach(a => {
            const upActive = a.uservote === 1 ? 'text-success' : 'text-muted';
            const downActive = a.uservote === -1 ? 'text-danger' : 'text-muted';
            const upDisabled = a.uservote === 1 ? 'disabled' : '';
            const downDisabled = a.uservote === -1 ? 'disabled' : '';

            html += `
                <div class="answer-card card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex">
                            <div class="d-flex flex-column align-items-center me-4">
                                <button class="btn btn-link p-0 vote-answer ${upActive}"
                                        data-id="${a.id}" data-value="1" ${upDisabled} title="Upvote">
                                    <i class="fa fa-chevron-up fa-2x"></i>
                                </button>
                                <span class="fw-bold fs-4 my-1">${a.votes}</span>
                                <button class="btn btn-link p-0 vote-answer ${downActive}"
                                        data-id="${a.id}" data-value="-1" ${downDisabled} title="Downvote">
                                    <i class="fa fa-chevron-down fa-2x"></i>
                                </button>
                            </div>

                            <div class="flex-grow-1">
                                <div class="answer-text mb-4 text-dark" style="line-height: 1.6;">${a.content}</div>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="actions">
                                        ${a.can_delete ? `
                                            <button class="btn btn-link btn-sm text-danger p-0 delete-answer" data-id="${a.id}">
                                                <i class="fa fa-trash me-1"></i>Delete
                                            </button>` : ''}
                                    </div>
                                    <div class="user-meta small p-2 rounded bg-light border">
                                        <span class="text-muted">answered by</span>
                                        <span class="fw-bold text-dark">${a.firstname} • ${timeAgo(a.timecreated)}</span>
                                        <span class="text-primary mx-1">${a.reputation}</span>
                                        ${a.badges.map(b =>`<span class="badge bg-info text-white ms-1">${b.name}</span>`).join('')}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
        });

        $(SELECTORS.container).html(html);
    }

    /**
     * Refresh post data with a loading overlay.
     */
    async function loadPost() {
        const $container = $(SELECTORS.container);
        $container.css('opacity', '0.5');

        try {
            const response = await fetch(`${M.cfg.wwwroot}/local/community/ajax/get_post.php?id=${currentPostId}`);
            const data = await response.json();
            render(data);
        } catch (error) {
            Notification.exception(error);
        } finally {
            $container.css('opacity', '1');
        }
    }

    /**
     * Initialize the post page logic.
     *
     * @param {Number} postid The post ID.
     */
    function init(postid) {
        currentPostId = postid;
        loadPost();

        $(document).on('click', '.vote-answer', async function(e) {
            e.preventDefault();
            const btn = $(this);
            const answerId = btn.data('id');
            const value = btn.data('value');

            const container = btn.closest('.d-flex.flex-column');
            container.find('.vote-answer').prop('disabled', true).addClass('opacity-50');

            try {
                await fetch(`${M.cfg.wwwroot}/local/community/ajax/vote.php`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        answerid: answerId,
                        value: value,
                        postid: currentPostId,
                        sesskey: M.cfg.sesskey
                    })
                });
                await loadPost();
            } catch (error) {
                Notification.exception(error);
            }
        });

        $(document).on('click', SELECTORS.submitBtn, async function(e) {
            e.preventDefault();
            const $btn = $(this);
            const content = $(SELECTORS.answerInput).val().trim();

            if (!content) {
                Notification.addNotification({
                    message: 'Please enter a response before posting.',
                    type: 'error'
                });
                return;
            }

            $btn.prop('disabled', true).html('<i class="fa fa-circle-o-notch fa-spin"></i> Posting...');

            try {
                await fetch(`${M.cfg.wwwroot}/local/community/ajax/create_answer.php`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({postid: currentPostId, content: content})
                });
                $(SELECTORS.answerInput).val('');
                await loadPost();
            } catch (error) {
                Notification.exception(error);
            } finally {
                $btn.html('Post Your Answer');
            }
        });

        $(document).on('click', '.delete-answer', function(e) {
            e.preventDefault();
            const id = $(this).data('id');

            Notification.confirm(
                'Confirm Deletion',
                'This action cannot be undone. Are you sure you want to delete this answer?',
                'Delete',
                'Cancel',
                async () => {
                    try {
                        await fetch(`${M.cfg.wwwroot}/local/community/ajax/delete_answer.php`, {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: new URLSearchParams({
                                answerid: id,
                                sesskey: M.cfg.sesskey
                            })
                        });
                        await loadPost();
                    } catch (error) {
                        Notification.exception(error);
                    }
                }
            );
        });

        $(document).on('input', SELECTORS.answerInput, function() {
            const hasContent = $(this).val().trim().length > 0;
            $(SELECTORS.submitBtn).prop('disabled', !hasContent);
        });
    }

    return {init: init};
});