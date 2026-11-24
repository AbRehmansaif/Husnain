(() => {
    'use strict';

    const fetchJson = async (url) => {
        const response = await fetch(url, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        if (!response.ok) {
            throw new Error('The request could not be completed.');
        }
        return response.json();
    };

    const navButton = document.querySelector('.nav-toggle');
    const navigation = document.querySelector('.primary-nav');
    if (navButton && navigation) {
        navButton.addEventListener('click', () => {
            const expanded = navButton.getAttribute('aria-expanded') === 'true';
            navButton.setAttribute('aria-expanded', String(!expanded));
            navigation.classList.toggle('is-open', !expanded);
        });
    }

    const registerForm = document.querySelector('[data-register-form]');
    if (registerForm) {
        const panels = {
            donor: registerForm.querySelector('[data-donor-fields]'),
            hospital: registerForm.querySelector('[data-hospital-fields]'),
        };
        const setRole = (role) => {
            Object.entries(panels).forEach(([name, panel]) => {
                if (!panel) return;
                const isActive = name === role;
                panel.hidden = !isActive;
                panel.querySelectorAll('input, select, textarea').forEach((field) => {
                    field.disabled = !isActive;
                });
            });
        };
        registerForm.querySelectorAll('input[name="role"]').forEach((radio) => {
            radio.addEventListener('change', () => setRole(radio.value));
        });
        const initialRole = registerForm.querySelector('input[name="role"]:checked');
        setRole(initialRole ? initialRole.value : 'donor');
    }

    document.querySelectorAll('[data-cnic], [data-phone]').forEach((field) => {
        const pattern = field.hasAttribute('data-cnic') ? /^\d{5}-\d{7}-\d$/ : /^03\d{2}-\d{7}$/;
        const hint = field.hasAttribute('data-cnic') ? 'Use the format 35202-1234567-1.' : 'Use the format 0300-1234567.';
        field.addEventListener('input', () => {
            const valid = field.value === '' || pattern.test(field.value);
            field.setCustomValidity(valid ? '' : hint);
            field.setAttribute('aria-invalid', String(!valid));
        });
    });

    document.querySelectorAll('[data-upload]').forEach((field) => {
        field.addEventListener('change', () => {
            const file = field.files && field.files[0];
            if (!file) return;
            const kind = field.dataset.upload;
            const photo = kind === 'photo';
            const allowedTypes = photo ? ['image/jpeg', 'image/png'] : ['application/pdf'];
            const maxBytes = photo ? 2 * 1024 * 1024 : 5 * 1024 * 1024;
            let message = '';
            if (!allowedTypes.includes(file.type)) {
                message = photo ? 'Choose a JPG or PNG image.' : 'Choose a PDF document.';
            } else if (file.size > maxBytes) {
                message = photo ? 'The profile photo must be smaller than 2 MB.' : 'The licence must be smaller than 5 MB.';
            }
            field.setCustomValidity(message);
            field.setAttribute('aria-invalid', String(Boolean(message)));
        });
    });

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });

    const statsSection = document.querySelector('[data-live-stats]');
    if (statsSection) {
        fetchJson(statsSection.dataset.liveStats)
            .then((stats) => {
                Object.entries(stats).forEach(([key, value]) => {
                    const target = statsSection.querySelector(`[data-stat="${key}"]`);
                    if (target) target.textContent = new Intl.NumberFormat().format(value);
                });
            })
            .catch(() => {
                statsSection.querySelectorAll('[data-stat]').forEach((target) => {
                    target.textContent = '—';
                });
            });
    }

    const emergencyBoard = document.querySelector('[data-emergency-board]');
    if (emergencyBoard && emergencyBoard.dataset.api) {
        const cityFilter = document.querySelector('[data-emergency-filter]');
        const loadEmergencies = async () => {
            const parameters = new URLSearchParams();
            if (cityFilter && cityFilter.value) parameters.set('city', cityFilter.value);
            try {
                const data = await fetchJson(`${emergencyBoard.dataset.api}&${parameters.toString()}`);
                emergencyBoard.replaceChildren();
                if (!data.requests.length) {
                    const empty = document.createElement('div');
                    empty.className = 'empty-state';
                    empty.innerHTML = '<span class="empty-symbol" aria-hidden="true">♡</span><h2>All quiet for now.</h2><p>There are no active requests in this area. Check back soon or join as a donor.</p>';
                    emergencyBoard.append(empty);
                    return;
                }
                data.requests.forEach((request) => emergencyBoard.append(createEmergencyCard(request)));
                updateCountdowns();
            } catch {
                emergencyBoard.innerHTML = '<div class="empty-state"><h2>We could not load requests just now.</h2><p>Please try again in a moment.</p></div>';
            }
        };
        if (cityFilter) cityFilter.addEventListener('change', loadEmergencies);
        loadEmergencies();
    }

    function createEmergencyCard(request) {
        const card = document.createElement('article');
        card.className = 'emergency-card';
        const top = document.createElement('div');
        top.className = 'emergency-card-top';
        const blood = document.createElement('span');
        blood.className = 'blood-chip';
        blood.textContent = request.blood_group;
        const urgency = document.createElement('span');
        urgency.className = `urgency urgency-${request.urgency}`;
        urgency.textContent = request.urgency[0].toUpperCase() + request.urgency.slice(1);
        top.append(blood, urgency);
        const city = document.createElement('h2');
        city.textContent = request.city_name;
        const units = document.createElement('p');
        const needed = Math.max(0, Number(request.units_needed) - Number(request.units_fulfilled) - Number(request.units_reserved));
        units.textContent = `${needed} unit${needed === 1 ? '' : 's'} still needed`;
        const countdown = document.createElement('div');
        countdown.className = 'countdown';
        countdown.dataset.countdown = request.required_by;
        const link = document.createElement('a');
        link.className = 'text-link';
        link.href = `${window.location.pathname}?page=register`;
        link.textContent = 'See how you can help →';
        card.append(top, city, units, countdown, link);
        return card;
    }

    const updateCountdowns = () => {
        document.querySelectorAll('[data-countdown]').forEach((element) => {
            const deadline = new Date(element.dataset.countdown).getTime();
            const remaining = deadline - Date.now();
            if (!Number.isFinite(deadline)) {
                element.textContent = 'Deadline unavailable';
            } else if (remaining <= 0) {
                element.textContent = 'Request deadline passed';
            } else {
                const hours = Math.floor(remaining / 3600000);
                const minutes = Math.floor((remaining % 3600000) / 60000);
                element.textContent = hours > 24
                    ? `Needed in ${Math.floor(hours / 24)} day${Math.floor(hours / 24) === 1 ? '' : 's'}`
                    : `Needed in ${hours}h ${minutes}m`;
            }
        });
    };
    updateCountdowns();
    window.setInterval(updateCountdowns, 60000);

    const donorSearch = document.querySelector('[data-donor-search]');
    if (donorSearch) {
        let timeout;
        donorSearch.addEventListener('input', () => {
            window.clearTimeout(timeout);
            const term = donorSearch.value.trim();
            const results = document.querySelector('[data-donor-results]');
            const ranked = document.querySelector('[data-ranked-donors]');
            if (ranked) ranked.hidden = term.length >= 2;
            if (term.length < 2) {
                if (results) results.replaceChildren();
                if (ranked) ranked.querySelectorAll('.rank-card').forEach((card) => { card.hidden = false; });
                return;
            }
            timeout = window.setTimeout(async () => {
                const parameters = new URLSearchParams({
                    api: 'donors',
                    request_id: donorSearch.dataset.requestId,
                    q: term,
                });
                try {
                    const data = await fetchJson(`${donorSearch.dataset.api.split('?')[0]}?${parameters.toString()}`);
                    if (!results) return;
                    results.replaceChildren();
                    const note = document.createElement('p');
                    note.className = 'search-result-note';
                    note.textContent = `${data.donors.length} eligible match${data.donors.length === 1 ? '' : 'es'} found · contact details stay private`;
                    results.append(note);
                    data.donors.forEach((donor) => {
                        const item = document.createElement('article');
                        item.className = 'rank-card';
                        const score = document.createElement('span');
                        score.className = 'rank-score';
                        score.textContent = donor.score;
                        const details = document.createElement('div');
                        const name = document.createElement('strong');
                        name.textContent = donor.name;
                        const summary = document.createElement('p');
                        summary.textContent = `${donor.blood_group} · ${donor.city}`;
                        details.append(name, summary);
                        item.append(score, details);
                        results.append(item);
                    });
                } catch {
                    if (results) results.textContent = 'Donor search is temporarily unavailable.';
                }
            }, 250);
        });
    }

    const renderChart = (container) => {
        let rows;
        try {
            rows = JSON.parse(container.dataset.values || '[]');
        } catch {
            container.textContent = 'Chart data is not available.';
            return;
        }
        container.replaceChildren();
        if (!rows.length) {
            const empty = document.createElement('p');
            empty.className = 'chart-empty';
            empty.textContent = 'No request activity to display yet.';
            container.append(empty);
            return;
        }
        if (container.dataset.chart === 'weekly') {
            const max = Math.max(1, ...rows.map((row) => Number(row.total)));
            rows.forEach((row) => {
                const wrap = document.createElement('div');
                wrap.className = 'chart-bar-wrap';
                const bar = document.createElement('i');
                bar.className = 'chart-bar';
                bar.style.height = `${Math.max(3, Number(row.total) / max * 100)}%`;
                bar.title = `${row.total} requests`;
                const label = document.createElement('span');
                label.className = 'chart-bar-label';
                label.textContent = row.week.slice(-2);
                wrap.append(bar, label);
                container.append(wrap);
            });
            return;
        }
        const max = Math.max(1, ...rows.map((row) => Number(row.demand)));
        rows.forEach((row) => {
            const line = document.createElement('div');
            line.className = 'demand-row';
            const label = document.createElement('span');
            label.textContent = row.blood_group;
            const track = document.createElement('div');
            track.className = 'demand-track';
            const demand = document.createElement('i');
            demand.style.width = `${Number(row.demand) / max * 100}%`;
            const supply = document.createElement('b');
            supply.style.width = `${Number(row.supply) / max * 100}%`;
            track.append(demand, supply);
            const values = document.createElement('small');
            values.className = 'demand-values';
            values.textContent = `${row.supply}/${row.demand}`;
            line.append(label, track, values);
            container.append(line);
        });
    };
    document.querySelectorAll('[data-chart]').forEach(renderChart);
})();
