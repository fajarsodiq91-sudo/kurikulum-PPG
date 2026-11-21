{{--
    Type-to-search multi-picker for task teams. Expects: $teams (collection of
    {key, label, ...}, already scoped to the hierarchy the current user/creator may assign to),
    $selectedKeys (list of pre-selected team keys, e.g. from old('teams') or the task's current
    teams).
--}}
@php
    $pickerTeams = $teams->map(fn ($team) => ['key' => $team['key'], 'label' => $team['label']])->values();
@endphp

<div
    data-team-picker
    data-teams="{{ $pickerTeams->toJson() }}"
    data-selected="{{ json_encode(array_values($selectedKeys ?? [])) }}"
>
    <div data-team-chips class="mb-2 flex flex-wrap gap-2"></div>
    <div class="relative">
        <input
            type="text"
            data-team-search
            autocomplete="off"
            placeholder="Ketik nama tim untuk mencari..."
            class="w-full rounded-lg border border-slate-300 px-3 py-2"
        >
        <div data-team-suggestions class="absolute z-10 mt-1 hidden max-h-56 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg"></div>
    </div>
    <div data-team-inputs></div>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-team-picker]').forEach(function (root) {
                const teams = JSON.parse(root.dataset.teams || '[]');
                const selectedKeys = new Set(JSON.parse(root.dataset.selected || '[]'));
                const byKey = new Map(teams.map((team) => [team.key, team]));

                const chipsEl = root.querySelector('[data-team-chips]');
                const inputsEl = root.querySelector('[data-team-inputs]');
                const searchEl = root.querySelector('[data-team-search]');
                const suggestionsEl = root.querySelector('[data-team-suggestions]');

                function escapeHtml(value) {
                    const div = document.createElement('div');
                    div.textContent = value;
                    return div.innerHTML;
                }

                function renderChips() {
                    chipsEl.replaceChildren();
                    inputsEl.replaceChildren();

                    selectedKeys.forEach(function (key) {
                        const team = byKey.get(key);

                        if (!team) {
                            return;
                        }

                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'teams[]';
                        hidden.value = key;
                        inputsEl.appendChild(hidden);

                        const chip = document.createElement('span');
                        chip.className = 'inline-flex items-center gap-1 rounded-full bg-sky-50 py-1 pl-2.5 pr-1.5 text-xs font-medium text-sky-700';
                        chip.title = team.label;
                        chip.append(document.createTextNode(team.label));

                        const remove = document.createElement('button');
                        remove.type = 'button';
                        remove.setAttribute('aria-label', 'Hapus '+team.label);
                        remove.className = 'text-sky-400 hover:text-sky-700';
                        remove.textContent = '×';
                        remove.addEventListener('click', function () {
                            selectedKeys.delete(key);
                            renderChips();
                            renderSuggestions();
                        });

                        chip.appendChild(remove);
                        chipsEl.appendChild(chip);
                    });
                }

                function renderSuggestions() {
                    const query = searchEl.value.trim().toLowerCase();
                    const matches = teams
                        .filter(function (team) {
                            return !selectedKeys.has(team.key) && (query === '' || team.label.toLowerCase().includes(query));
                        })
                        .slice(0, 8);

                    suggestionsEl.replaceChildren();

                    if (matches.length === 0) {
                        suggestionsEl.classList.add('hidden');

                        return;
                    }

                    matches.forEach(function (team) {
                        const option = document.createElement('button');
                        option.type = 'button';
                        option.className = 'block w-full px-3 py-2 text-left text-sm hover:bg-slate-50';
                        option.innerHTML = escapeHtml(team.label);
                        option.addEventListener('click', function () {
                            selectedKeys.add(team.key);
                            searchEl.value = '';
                            renderChips();
                            renderSuggestions();
                            suggestionsEl.classList.add('hidden');
                            searchEl.focus();
                        });
                        suggestionsEl.appendChild(option);
                    });

                    suggestionsEl.classList.remove('hidden');
                }

                searchEl.addEventListener('input', renderSuggestions);
                searchEl.addEventListener('focus', renderSuggestions);

                document.addEventListener('click', function (event) {
                    if (!root.contains(event.target)) {
                        suggestionsEl.classList.add('hidden');
                    }
                });

                renderChips();
            });
        });
    </script>
@endonce
