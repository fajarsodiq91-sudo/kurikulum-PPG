{{--
    Type-to-search multi-picker for task assignees. Expects: $recipients (already scoped to the
    hierarchy the current user/creator may assign to), $selectedIds (list of pre-selected ids,
    e.g. from old('assignees') or the task's current assignees).
--}}
@php
    $pickerRecipients = $recipients->map(fn ($recipient) => [
        'id' => $recipient->id,
        'name' => $recipient->name,
        'roles' => $recipient->roles->pluck('name')->join(', '),
    ])->values();
@endphp

<div
    data-assignee-picker
    data-recipients="{{ $pickerRecipients->toJson() }}"
    data-selected="{{ json_encode(array_values($selectedIds ?? [])) }}"
>
    <div data-assignee-chips class="mb-2 flex flex-wrap gap-2"></div>
    <div class="relative">
        <input
            type="text"
            data-assignee-search
            autocomplete="off"
            placeholder="Ketik nama pengurus untuk mencari..."
            class="w-full rounded-lg border border-slate-300 px-3 py-2"
        >
        <div data-assignee-suggestions class="absolute z-10 mt-1 hidden max-h-56 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg"></div>
    </div>
    <div data-assignee-inputs></div>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-assignee-picker]').forEach(function (root) {
                const recipients = JSON.parse(root.dataset.recipients || '[]');
                const selectedIds = new Set(JSON.parse(root.dataset.selected || '[]').map(Number));
                const byId = new Map(recipients.map((recipient) => [recipient.id, recipient]));

                const chipsEl = root.querySelector('[data-assignee-chips]');
                const inputsEl = root.querySelector('[data-assignee-inputs]');
                const searchEl = root.querySelector('[data-assignee-search]');
                const suggestionsEl = root.querySelector('[data-assignee-suggestions]');

                function escapeHtml(value) {
                    const div = document.createElement('div');
                    div.textContent = value;
                    return div.innerHTML;
                }

                function renderChips() {
                    chipsEl.replaceChildren();
                    inputsEl.replaceChildren();

                    selectedIds.forEach(function (id) {
                        const recipient = byId.get(id);

                        if (!recipient) {
                            return;
                        }

                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'assignees[]';
                        hidden.value = id;
                        inputsEl.appendChild(hidden);

                        const chip = document.createElement('span');
                        chip.className = 'inline-flex items-center gap-1 rounded-full bg-brand-50 px-3 py-1 text-xs font-medium text-brand-700';
                        chip.textContent = recipient.name;

                        const remove = document.createElement('button');
                        remove.type = 'button';
                        remove.setAttribute('aria-label', 'Hapus '+recipient.name);
                        remove.className = 'text-brand-400 hover:text-brand-700';
                        remove.textContent = '×';
                        remove.addEventListener('click', function () {
                            selectedIds.delete(id);
                            renderChips();
                            renderSuggestions();
                        });

                        chip.appendChild(remove);
                        chipsEl.appendChild(chip);
                    });
                }

                function renderSuggestions() {
                    const query = searchEl.value.trim().toLowerCase();
                    const matches = recipients
                        .filter(function (recipient) {
                            return !selectedIds.has(recipient.id) && (query === '' || recipient.name.toLowerCase().includes(query));
                        })
                        .slice(0, 8);

                    suggestionsEl.replaceChildren();

                    if (matches.length === 0) {
                        suggestionsEl.classList.add('hidden');

                        return;
                    }

                    matches.forEach(function (recipient) {
                        const option = document.createElement('button');
                        option.type = 'button';
                        option.className = 'flex w-full flex-col px-3 py-2 text-left text-sm hover:bg-slate-50';
                        option.innerHTML = '<span class="font-medium text-slate-800">'+escapeHtml(recipient.name)+'</span>'
                            +(recipient.roles ? '<span class="text-xs text-slate-400">'+escapeHtml(recipient.roles)+'</span>' : '');
                        option.addEventListener('click', function () {
                            selectedIds.add(recipient.id);
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
