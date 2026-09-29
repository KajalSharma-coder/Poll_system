const ADMIN_API = 'php';

let adminUser = null;

document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    checkAdminAuth();
    setupThemeListeners();
});

function initTheme() {
    const saved = localStorage.getItem('poll-theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);
    if (saved === 'dark') document.documentElement.setAttribute('data-bs-theme', 'dark');
    document.querySelectorAll('.theme-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.theme === saved);
    });
}

function setupThemeListeners() {
    document.querySelectorAll('.theme-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const theme = btn.dataset.theme;
            document.documentElement.setAttribute('data-theme', theme);
            if (theme === 'dark') {
                document.documentElement.setAttribute('data-bs-theme', 'dark');
            } else {
                document.documentElement.removeAttribute('data-bs-theme');
            }
            document.querySelectorAll('.theme-btn').forEach(b => b.classList.toggle('active', b.dataset.theme === theme));
            localStorage.setItem('poll-theme', theme);
        });
    });

    document.getElementById('logoutFromAdmin').addEventListener('click', async () => {
        await fetch('../php/logout.php');
        window.location.href = '../login.html';
    });
}

async function checkAdminAuth() {
    try {
        const res = await fetch('../php/check_session.php');
        const data = await res.json();
        if (data.logged_in && data.user.role === 'admin') {
            adminUser = data.user;
            loadAdminPolls();
        } else {
            window.location.href = '../login.html';
        }
    } catch {
        window.location.href = '../login.html';
    }
}

async function loadAdminPolls() {
    const container = document.getElementById('adminPollsContainer');
    container.innerHTML = `<div class="loading-container"><div class="spinner"></div></div>`;

    try {
        const res = await fetch(`${ADMIN_API}/get_polls.php`);
        const data = await res.json();
        if (data.success) {
            renderAdminPolls(data.polls);
        } else {
            showAdminError(data.error || 'Failed to load polls');
        }
    } catch {
        showAdminError('Could not connect to the server.');
    }
}

function renderAdminPolls(polls) {
    const container = document.getElementById('adminPollsContainer');
    if (!polls || polls.length === 0) {
        container.innerHTML = `<div class="empty-state"><h4>No polls yet</h4><p>Create your first poll above.</p></div>`;
        return;
    }
    container.innerHTML = '<h5 class="fw-bold mb-3">Manage Polls</h5>';

    polls.forEach(poll => {
        const item = document.createElement('div');
        item.className = 'admin-poll-item';
        item.dataset.pollId = poll.id;

        const header = document.createElement('div');
        header.className = 'admin-poll-header';
        header.innerHTML = `
            <h5>${poll.question}</h5>
            <div class="admin-actions-bar">
                <button class="btn-del-poll" onclick="deletePoll(${poll.id})">Delete Poll</button>
            </div>
        `;
        item.appendChild(header);

        const optionsDiv = document.createElement('div');
        optionsDiv.className = 'admin-poll-options';

        poll.options.forEach(opt => {
            optionsDiv.appendChild(createOptionRow(poll.id, opt));
        });

        const addOptBtn = document.createElement('button');
        addOptBtn.className = 'add-option-btn mt-2';
        addOptBtn.textContent = '+ Add Option';
        addOptBtn.onclick = () => addOption(poll.id);
        optionsDiv.appendChild(addOptBtn);

        item.appendChild(optionsDiv);
        container.appendChild(item);
    });
}

function createOptionRow(pollId, opt) {
    const row = document.createElement('div');
    row.className = 'admin-option-item';
    row.dataset.optionId = opt.id;

    const textSpan = document.createElement('span');
    textSpan.className = 'admin-option-text';
    textSpan.textContent = opt.option_text;

    const votesDiv = document.createElement('div');
    votesDiv.className = 'admin-option-votes';
    votesDiv.innerHTML = `
        <span style="font-size:0.85rem;color:var(--text-secondary);">Votes:</span>
        <input type="number" min="0" value="${opt.vote_count}" class="vote-count-input">
    `;

    const actions = document.createElement('div');
    actions.className = 'admin-option-actions';
    actions.innerHTML = `
        <button class="btn-save" onclick="saveVotes(this, ${opt.id})">Save</button>
        <button class="btn-del-opt" onclick="deleteOption(${opt.id}, this)">Delete</button>
    `;

    row.appendChild(textSpan);
    row.appendChild(votesDiv);
    row.appendChild(actions);
    return row;
}

function showAdminError(message) {
    const container = document.getElementById('adminPollsContainer');
    container.innerHTML = `
        <div class="error-container">
            <h4>Error</h4>
            <p>${message}</p>
            <button class="btn btn-outline-primary mt-3" onclick="loadAdminPolls()">Retry</button>
        </div>
    `;
}

async function createPoll() {
    const question = document.getElementById('newPollQuestion').value.trim();
    if (!question) {
        showToast('Please enter a poll question.', 'error');
        return;
    }

    const inputs = document.querySelectorAll('#newPollOptions .create-option-input');
    const options = [];
    inputs.forEach(inp => {
        const val = inp.value.trim();
        if (val) options.push(val);
    });

    if (options.length < 2) {
        showToast('Please add at least 2 options.', 'error');
        return;
    }

    const btn = document.getElementById('createPollBtn');
    btn.disabled = true;
    btn.textContent = 'Creating...';

    try {
        const res = await fetch(`${ADMIN_API}/create_poll.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ question, options })
        });
        const data = await res.json();
        if (data.success) {
            showToast('Poll created successfully!', 'success');
            document.getElementById('newPollQuestion').value = '';
            document.querySelectorAll('#newPollOptions .create-option-input').forEach(inp => inp.value = '');
            loadAdminPolls();
        } else {
            showToast(data.error || 'Failed to create poll.', 'error');
        }
    } catch {
        showToast('Could not connect to server.', 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Create Poll';
    }
}

async function deletePoll(pollId) {
    if (!confirm('Are you sure you want to delete this poll and all its data?')) return;

    try {
        const res = await fetch(`${ADMIN_API}/delete_poll.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ poll_id: pollId })
        });
        const data = await res.json();
        if (data.success) {
            showToast('Poll deleted.', 'success');
            loadAdminPolls();
        } else {
            showToast(data.error || 'Failed to delete poll.', 'error');
        }
    } catch {
        showToast('Could not connect to server.', 'error');
    }
}

async function saveVotes(btn, optionId) {
    const row = btn.closest('.admin-option-item');
    const input = row.querySelector('.vote-count-input');
    const count = parseInt(input.value);
    if (isNaN(count) || count < 0) {
        showToast('Enter a valid number.', 'error');
        return;
    }

    try {
        const res = await fetch(`${ADMIN_API}/update_votes.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ option_id: optionId, vote_count: count })
        });
        const data = await res.json();
        if (data.success) {
            showToast('Votes updated.', 'success');
        } else {
            showToast(data.error || 'Failed to update.', 'error');
        }
    } catch {
        showToast('Could not connect to server.', 'error');
    }
}

async function deleteOption(optionId, btn) {
    if (!confirm('Remove this option? Votes for it will be lost.')) return;

    try {
        const res = await fetch(`${ADMIN_API}/delete_option.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ option_id: optionId })
        });
        const data = await res.json();
        if (data.success) {
            showToast('Option deleted.', 'success');
            const row = btn.closest('.admin-option-item');
            row.remove();
        } else {
            showToast(data.error || 'Failed to delete option.', 'error');
        }
    } catch {
        showToast('Could not connect to server.', 'error');
    }
}

async function addOption(pollId) {
    const text = prompt('Enter the new option text:');
    if (!text || !text.trim()) return;

    try {
        const res = await fetch(`${ADMIN_API}/add_option.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ poll_id: pollId, option_text: text.trim() })
        });
        const data = await res.json();
        if (data.success) {
            showToast('Option added.', 'success');
            loadAdminPolls();
        } else {
            showToast(data.error || 'Failed to add option.', 'error');
        }
    } catch {
        showToast('Could not connect to server.', 'error');
    }
}

function addOptionRow() {
    const container = document.getElementById('newPollOptions');
    const row = document.createElement('div');
    row.className = 'option-row';
    row.innerHTML = `
        <input type="text" class="form-control create-option-input" placeholder="Option ${container.children.length + 1}">
        <button class="btn-remove-opt" onclick="removeOptionRow(this)" title="Remove option">&times;</button>
    `;
    container.appendChild(row);
}

function removeOptionRow(btn) {
    const row = btn.closest('.option-row');
    if (document.querySelectorAll('#newPollOptions .option-row').length <= 2) {
        showToast('Need at least 2 options.', 'error');
        return;
    }
    row.remove();
}

function showToast(message, type) {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `admin-toast ${type}`;
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 2500);
}
