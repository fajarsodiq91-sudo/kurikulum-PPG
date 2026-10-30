{{-- Single shared detail popup for every clickable pengurus entry in the struktur organisasi chart. --}}
<div id="person-detail-backdrop" class="fixed inset-0 z-40 hidden bg-slate-900/50" data-person-detail-close></div>

<div id="person-detail-modal" class="fixed inset-0 z-50 hidden overflow-y-auto p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="person-detail-name">
    <div class="mx-auto my-16 w-full max-w-sm">
        <div class="relative rounded-2xl bg-white p-6 shadow-xl">
            <button type="button" class="absolute right-4 top-4 text-xl leading-none text-slate-400 hover:text-slate-700" aria-label="Tutup" data-person-detail-close>&times;</button>

            <div class="flex flex-col items-center text-center">
                <div id="person-detail-avatar" class="mb-3 h-20 w-20 overflow-hidden rounded-full bg-brand-100"></div>
                <p id="person-detail-name" class="text-lg font-bold text-slate-950"></p>
                <p id="person-detail-jabatan" class="text-sm text-slate-500"></p>
            </div>

            <div id="person-detail-actions" class="mt-5 flex items-center justify-center gap-3"></div>
        </div>
    </div>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const backdrop = document.getElementById('person-detail-backdrop');
            const modal = document.getElementById('person-detail-modal');

            if (!backdrop || !modal) {
                return;
            }

            const avatarEl = document.getElementById('person-detail-avatar');
            const nameEl = document.getElementById('person-detail-name');
            const jabatanEl = document.getElementById('person-detail-jabatan');
            const actionsEl = document.getElementById('person-detail-actions');

            const icons = {
                mail: '<svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5"><path d="M1.5 8.67v8.58a3 3 0 003 3h15a3 3 0 003-3V8.67l-8.928 5.493a3 3 0 01-3.144 0L1.5 8.67z"/><path d="M22.5 6.908V6.75a3 3 0 00-3-3h-15a3 3 0 00-3 3v.158l9.714 5.978a1.5 1.5 0 001.572 0L22.5 6.908z"/></svg>',
                whatsapp: '<svg viewBox="0 0 448 512" fill="currentColor" class="h-5 w-5"><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 76.3 29.6 109L2 480l127.6-33.4c31.7 17.3 67.6 26.4 104.2 26.4h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-77.1-157zm-157 341.6c-32.5 0-64.3-8.7-92-25.2l-6.6-3.9-68.1 17.8L75.5 372l-4.3-6.9c-18.1-28.7-27.6-61.9-27.6-96 0-100.9 82.1-183 183.1-183 48.9 0 94.9 19.1 129.5 53.7 34.6 34.6 56.1 80.6 56 129.5 0 101-83.4 183.4-184.3 183.4zm100.4-137.2c-5.5-2.8-32.5-16-37.6-17.9-5-1.9-8.7-2.8-12.4 2.8-3.7 5.5-14.2 17.9-17.4 21.6-3.2 3.7-6.4 4.1-11.9 1.4-32.2-16.1-53.3-28.7-74.5-65.1-5.6-9.7 5.6-9 16-30.1 1.8-3.7.9-6.9-.6-9.7-1.5-2.8-13.4-32.3-18.3-44.2-4.8-11.6-9.8-10-13.5-10.2-3.5-.2-7.5-.2-11.5-.2-4 0-10.4 1.5-15.9 7.4-5.5 6-21 20.6-21 50.2 0 29.6 21.5 58.2 24.5 62.2 3 4.1 41.8 63.9 101.4 87.1 50.6 19.9 60.9 16 71.9 15 11-1 35.5-14.5 40.5-28.5 5-14 5-26 3.5-28.5s-5.1-4-10.6-6.7z"/></svg>',
                task: '<svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M10.5 3A1.5 1.5 0 009 4.5v.75H5.25A2.25 2.25 0 003 7.5v12A2.25 2.25 0 005.25 21.75h13.5A2.25 2.25 0 0021 19.5v-12A2.25 2.25 0 0018.75 5.25H15V4.5A1.5 1.5 0 0013.5 3h-3zm0 1.5h3V6h-3V4.5zm-3.97 9.53a.75.75 0 011.06-1.06l1.72 1.72 3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0l-2.25-2.25z" clip-rule="evenodd"/></svg>',
            };

            function initialsOf(name) {
                const parts = name.trim().split(/\s+/).filter(Boolean);

                if (parts.length === 0) {
                    return '';
                }

                if (parts.length === 1) {
                    return parts[0].slice(0, 2).toUpperCase();
                }

                return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
            }

            function actionLink(href, label, icon, colorClass, openInNewTab) {
                const a = document.createElement('a');
                a.href = href;
                a.title = label;
                a.setAttribute('aria-label', label);
                a.className = 'flex h-11 w-11 items-center justify-center rounded-full text-white transition ' + colorClass;
                a.innerHTML = icon;

                if (openInNewTab) {
                    a.target = '_blank';
                    a.rel = 'noopener';
                }

                return a;
            }

            function closeModal() {
                backdrop.classList.add('hidden');
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            function openModal(person) {
                nameEl.textContent = person.name;
                jabatanEl.textContent = person.jabatan;

                avatarEl.replaceChildren();

                if (person.photo) {
                    const img = document.createElement('img');
                    img.src = person.photo;
                    img.alt = person.name;
                    img.className = 'h-full w-full object-cover';
                    avatarEl.appendChild(img);
                } else {
                    const span = document.createElement('span');
                    span.className = 'flex h-full w-full items-center justify-center text-xl font-bold text-brand-700';
                    span.textContent = initialsOf(person.name);
                    avatarEl.appendChild(span);
                }

                actionsEl.replaceChildren();

                if (person.email) {
                    actionsEl.appendChild(actionLink('mailto:' + person.email, 'Kirim email', icons.mail, 'bg-slate-500 hover:bg-slate-600', false));
                }

                if (person.whatsappUrl) {
                    actionsEl.appendChild(actionLink(person.whatsappUrl, 'Chat WhatsApp', icons.whatsapp, 'bg-emerald-500 hover:bg-emerald-600', true));
                }

                if (person.taskUrl) {
                    actionsEl.appendChild(actionLink(person.taskUrl, 'Beri tugas', icons.task, 'bg-amber-500 hover:bg-amber-600', false));
                }

                backdrop.classList.remove('hidden');
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            document.querySelectorAll('[data-person-detail]').forEach(function (button) {
                button.addEventListener('click', function () {
                    openModal(JSON.parse(button.dataset.personDetail));
                });
            });

            document.querySelectorAll('[data-person-detail-close]').forEach(function (el) {
                el.addEventListener('click', closeModal);
            });
        });
    </script>
@endonce
