const sidebar = document.querySelector('#app-sidebar');
const backdrop = document.querySelector('#sidebar-backdrop');
const toggle = document.querySelector('[data-sidebar-toggle]');

const closeSidebar = () => {
	sidebar?.classList.add('-translate-x-full');
	backdrop?.classList.add('hidden');
	toggle?.setAttribute('aria-expanded', 'false');
};

const openSidebar = () => {
	sidebar?.classList.remove('-translate-x-full');
	backdrop?.classList.remove('hidden');
	toggle?.setAttribute('aria-expanded', 'true');
};

toggle?.addEventListener('click', () => {
	sidebar?.classList.contains('-translate-x-full') ? openSidebar() : closeSidebar();
});

backdrop?.addEventListener('click', closeSidebar);

let sidebarSearching = false;

document.querySelectorAll('[data-sidebar-accordion]').forEach((details) => {
	details.addEventListener('toggle', () => {
		if (sidebarSearching || !details.open) {
			return;
		}

		document.querySelectorAll('[data-sidebar-accordion]').forEach((other) => {
			if (other !== details) {
				other.open = false;
			}
		});
	});
});

const sidebarSearch = document.querySelector('[data-sidebar-search]');
const sidebarEmpty = document.querySelector('[data-sidebar-empty]');

sidebarSearch?.addEventListener('input', () => {
	const query = sidebarSearch.value.trim().toLowerCase();
	sidebarSearching = query.length > 0;
	let anyMatch = false;

	document.querySelectorAll('[data-sidebar-group]').forEach((group) => {
		let groupHasMatch = false;

		group.querySelectorAll(':scope > [data-sidebar-section]').forEach((section) => {
			const matches = !query || section.textContent.toLowerCase().includes(query);
			section.classList.toggle('hidden', !matches);

			if (matches) {
				groupHasMatch = true;
				anyMatch = true;
			}

			if (section.matches('[data-sidebar-accordion]')) {
				section.open = query ? matches : section.dataset.initialOpen === '1';
			}
		});

		group.classList.toggle('hidden', !groupHasMatch);
	});

	sidebarEmpty?.classList.toggle('hidden', anyMatch || !query);
});

const profileToggle = document.querySelector('[data-profile-toggle]');
const profileMenu = document.querySelector('[data-profile-menu]');

const closeProfileMenu = () => {
	profileMenu?.classList.add('hidden');
	profileToggle?.setAttribute('aria-expanded', 'false');
};

const openProfileMenu = () => {
	profileMenu?.classList.remove('hidden');
	profileToggle?.setAttribute('aria-expanded', 'true');
};

profileToggle?.addEventListener('click', (event) => {
	event.stopPropagation();
	profileMenu?.classList.contains('hidden') ? openProfileMenu() : closeProfileMenu();
});

document.addEventListener('click', (event) => {
	if (profileMenu && !profileMenu.classList.contains('hidden') && !profileMenu.contains(event.target)) {
		closeProfileMenu();
	}
});
