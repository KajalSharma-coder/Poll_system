const API = {
    checkSession: 'php/check_session.php',
    getPolls: 'php/get_polls.php',
    vote: 'php/vote.php',
    logout: 'php/logout.php'
};

let currentUser = null;
let currentVote = null;

document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    checkAuth();
    setupEventListeners();
});

function setupEventListeners() {
    document.querySelectorAll('.theme-btn').forEach(btn => {
        btn.addEventListener('click', () => setTheme(btn.dataset.theme));
    });

    document.getElementById('confirmVoteBtn').addEventListener('click', submitVote);

    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', handleLogout);
    }
}

function initTheme() {
    const saved = localStorage.getItem('poll-theme') || 'light';
    setTheme(saved);
}

function setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    if (theme === 'dark') {
        document.documentElement.setAttribute('data-bs-theme', 'dark');
    } else {
        document.documentElement.removeAttribute('data-bs-theme');
    }
    document.querySelectorAll('.theme-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.theme === theme);
    });
    localStorage.setItem('poll-theme', theme);
}

async function checkAuth() {
    try {
        const res = await fetch(API.checkSession);
        const data = await res.json();
        currentUser = data.logged_in ? data.user : null;
    } catch {
        currentUser = null;
    }
    updateUI();
    loadPolls();
}

function updateUI() {
    const userDisplay = document.getElementById('userDisplay');
    const loginBtn = document.getElementById('loginBtn');
    const logoutBtn = document.getElementById('logoutBtn');
    const adminLink = document.getElementById('adminLink');
    const subtitle = document.getElementById('pageSubtitle');

    if (currentUser) {
        userDisplay.textContent = currentUser.display_name;
        userDisplay.classList.remove('d-none');
        loginBtn.classList.add('d-none');
        logoutBtn.classList.remove('d-none');
        subtitle.textContent = `Welcome, ${currentUser.display_name}! Vote on the topics below.`;

        if (currentUser.role === 'admin') {
            adminLink.classList.remove('d-none');
        } else {
            adminLink.classList.add('d-none');
        }
    } else {
        userDisplay.classList.add('d-none');
        loginBtn.classList.remove('d-none');
        logoutBtn.classList.add('d-none');
        adminLink.classList.add('d-none');
        subtitle.textContent = 'Login to vote on the topics below and see live results.';
    }
}

async function handleLogout() {
    try {
        await fetch(API.logout);
    } catch {}
    currentUser = null;
    updateUI();
    loadPolls();
}

async function loadPolls() {
    const container = document.getElementById('pollsContainer');
    container.innerHTML = `
        <div class="loading-container">
            <div class="spinner"></div>
        </div>
    `;

    try {
        const res = await fetch(API.getPolls);
        const data = await res.json();

        if (data.success && data.polls.length > 0) {
            renderPolls(data.polls);
        } else if (data.success && data.polls.length === 0) {
            container.innerHTML = `
                <div class="col-12">
                    <div class="empty-state">
                        <h4>No polls available</h4>
                        <p>Check back later for new polls.</p>
                    </div>
                </div>
            `;
        } else {
            showError('Failed to load polls');
        }
    } catch {
        showError('Could not connect to the server. Make sure XAMPP is running.');
    }
}

function showError(message) {
    const container = document.getElementById('pollsContainer');
    container.innerHTML = `
        <div class="col-12">
            <div class="error-container">
                <h4>Oops!</h4>
                <p>${message}</p>
                <button class="btn btn-outline-primary mt-3" onclick="loadPolls()">Try Again</button>
            </div>
        </div>
    `;
}

function renderPolls(polls) {
    const container = document.getElementById('pollsContainer');
    container.innerHTML = '';

    polls.forEach(poll => {
        const col = document.createElement('div');
        col.className = 'col-lg-6';

        const card = document.createElement('div');
        card.className = 'card poll-card h-100';
        card.dataset.pollId = poll.id;

        const body = document.createElement('div');
        body.className = 'card-body';

        const question = document.createElement('h5');
        question.className = 'card-title';
        question.textContent = poll.question;
        body.appendChild(question);

        const totalVotes = document.createElement('p');
        totalVotes.className = 'total-votes';
        totalVotes.textContent = `${poll.total_votes} total vote${poll.total_votes !== 1 ? 's' : ''}`;
        body.appendChild(totalVotes);

        if (poll.voted) {
            body.appendChild(renderResults(poll, poll.voted_option_id));
            const badge = document.createElement('div');
            badge.className = 'voted-badge';
            badge.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                Voted
            `;
            body.appendChild(badge);
        } else {
            body.appendChild(renderOptions(poll));

            if (!currentUser) {
                const loginPrompt = document.createElement('div');
                loginPrompt.className = 'mt-3 text-center';
                loginPrompt.innerHTML = `<a href="login.html" class="btn vote-btn text-white">Login to Vote</a>`;
                body.appendChild(loginPrompt);
            } else {
                const voteBtn = document.createElement('button');
                voteBtn.className = 'btn vote-btn text-white mt-3';
                voteBtn.textContent = 'Vote';
                voteBtn.dataset.pollId = poll.id;
                voteBtn.dataset.bsToggle = 'modal';
                voteBtn.dataset.bsTarget = '#voteModal';
                voteBtn.addEventListener('click', () => openVoteModal(poll.id, poll.question));
                body.appendChild(voteBtn);
            }
        }

        card.appendChild(body);
        col.appendChild(card);
        container.appendChild(col);
    });
}

function renderOptions(poll) {
    const div = document.createElement('div');
    div.className = 'poll-options';

    poll.options.forEach(opt => {
        const wrapper = document.createElement('div');
        wrapper.className = 'form-check poll-option';

        const input = document.createElement('input');
        input.className = 'form-check-input';
        input.type = 'radio';
        input.name = `poll_${poll.id}`;
        input.value = opt.id;
        input.id = `opt_${opt.id}`;

        const label = document.createElement('label');
        label.className = 'form-check-label';
        label.htmlFor = `opt_${opt.id}`;
        label.textContent = opt.option_text;

        wrapper.appendChild(input);
        wrapper.appendChild(label);
        wrapper.addEventListener('click', () => {
            input.checked = true;
            document.querySelectorAll(`[name="poll_${poll.id}"]`).forEach(el => {
                el.closest('.poll-option').classList.remove('has-vote');
            });
            wrapper.classList.add('has-vote');
        });
        div.appendChild(wrapper);
    });

    return div;
}

function renderResults(poll, votedOptionId) {
    const div = document.createElement('div');
    div.className = 'poll-results';

    poll.options.forEach(opt => {
        const item = document.createElement('div');
        item.className = 'poll-option-result mb-3';

        const header = document.createElement('div');
        header.className = 'd-flex justify-content-between mb-1';

        const label = document.createElement('span');
        label.className = 'small fw-medium';
        label.textContent = opt.option_text;
        if (votedOptionId && opt.id === votedOptionId) {
            label.textContent += ' (Your vote)';
            label.style.color = 'var(--accent-color)';
        }

        const value = document.createElement('span');
        value.className = 'small text-secondary';
        value.textContent = `${opt.percentage}% (${opt.vote_count})`;

        header.appendChild(label);
        header.appendChild(value);

        const progress = document.createElement('div');
        progress.className = 'progress';

        const bar = document.createElement('div');
        bar.className = 'progress-bar';
        bar.style.width = '0%';
        bar.setAttribute('role', 'progressbar');
        bar.setAttribute('aria-valuenow', opt.percentage);
        bar.setAttribute('aria-valuemin', '0');
        bar.setAttribute('aria-valuemax', '100');

        progress.appendChild(bar);
        item.appendChild(header);
        item.appendChild(progress);
        div.appendChild(item);

        requestAnimationFrame(() => {
            bar.style.width = `${opt.percentage}%`;
        });
    });

    return div;
}

function openVoteModal(pollId, question) {
    if (!currentUser) {
        window.location.href = 'login.html';
        return;
    }

    const selected = document.querySelector(`input[name="poll_${pollId}"]:checked`);
    if (!selected) {
        alert('Please select an option before voting.');
        return;
    }

    const optionText = selected.closest('.poll-option').querySelector('.form-check-label').textContent;

    currentVote = { pollId, optionId: parseInt(selected.value) };
    document.getElementById('modalPollQuestion').textContent = question;
    document.getElementById('modalSelectedOption').innerHTML = `You are voting for: <strong>${optionText}</strong>`;
}

async function submitVote() {
    if (!currentVote) return;

    const btn = document.getElementById('confirmVoteBtn');
    btn.disabled = true;
    btn.textContent = 'Submitting...';

    try {
        const res = await fetch(API.vote, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                poll_id: currentVote.pollId,
                option_id: currentVote.optionId
            })
        });

        const data = await res.json();

        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('voteModal'));
            if (modal) modal.hide();
            updatePollCard(data.poll);
        } else {
            if (res.status === 401) {
                window.location.href = 'login.html';
                return;
            }
            alert(data.error || 'Failed to submit vote.');
        }
    } catch {
        alert('Could not connect to the server. Is XAMPP running?');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Submit Vote';
        currentVote = null;
    }
}

function updatePollCard(poll) {
    const cards = document.querySelectorAll('.poll-card');
    for (const card of cards) {
        if (card.dataset.pollId == poll.id) {
            const body = card.querySelector('.card-body');
            body.innerHTML = '';

            const question = document.createElement('h5');
            question.className = 'card-title';
            question.textContent = poll.question;
            body.appendChild(question);

            const totalVotes = document.createElement('p');
            totalVotes.className = 'total-votes';
            totalVotes.textContent = `${poll.total_votes} total vote${poll.total_votes !== 1 ? 's' : ''}`;
            body.appendChild(totalVotes);

            body.appendChild(renderResults(poll, poll.voted_option_id));

            const badge = document.createElement('div');
            badge.className = 'voted-badge';
            badge.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                Voted
            `;
            body.appendChild(badge);

            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            break;
        }
    }
}
