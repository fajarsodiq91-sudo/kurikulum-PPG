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

const sidebarCollapseToggle = document.querySelector('[data-sidebar-collapse-toggle]');
const appMain = document.querySelector('#app-main');
const SIDEBAR_COLLAPSE_KEY = 'sidebar-collapsed';

const applySidebarCollapsed = (collapsed) => {
	sidebar?.classList.toggle('is-collapsed', collapsed);
	appMain?.classList.toggle('is-collapsed', collapsed);
	sidebarCollapseToggle?.setAttribute('aria-pressed', String(collapsed));
};

try {
	if (window.localStorage.getItem(SIDEBAR_COLLAPSE_KEY) === '1') {
		applySidebarCollapsed(true);
	}
} catch {
	// localStorage unavailable (e.g. private browsing) — default to expanded.
}

sidebarCollapseToggle?.addEventListener('click', () => {
	const collapsed = !sidebar?.classList.contains('is-collapsed');
	applySidebarCollapsed(collapsed);

	try {
		window.localStorage.setItem(SIDEBAR_COLLAPSE_KEY, collapsed ? '1' : '0');
	} catch {
		// localStorage unavailable (e.g. private browsing) — state just won't persist.
	}
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

document.querySelectorAll('[data-multiselect]').forEach((container) => {
	const multiselectToggle = container.querySelector('[data-multiselect-toggle]');
	const panel = container.querySelector('[data-multiselect-panel]');
	const label = container.querySelector('[data-multiselect-label]');
	const placeholder = container.dataset.multiselectPlaceholder ?? '';
	const checkboxes = container.querySelectorAll('[data-multiselect-option]');

	const closeMultiselect = () => {
		panel?.classList.add('hidden');
		multiselectToggle?.setAttribute('aria-expanded', 'false');
	};

	const openMultiselect = () => {
		panel?.classList.remove('hidden');
		multiselectToggle?.setAttribute('aria-expanded', 'true');
	};

	const updateMultiselectLabel = () => {
		if (!label) {
			return;
		}

		const checkedLabels = Array.from(checkboxes)
			.filter((checkbox) => checkbox.checked)
			.map((checkbox) => checkbox.closest('[data-multiselect-item]')?.dataset.multiselectItemLabel ?? '');

		label.textContent = checkedLabels.length ? checkedLabels.join(', ') : placeholder;
	};

	multiselectToggle?.addEventListener('click', (event) => {
		event.stopPropagation();
		panel?.classList.contains('hidden') ? openMultiselect() : closeMultiselect();
	});

	checkboxes.forEach((checkbox) => {
		checkbox.addEventListener('change', updateMultiselectLabel);
	});

	document.addEventListener('click', (event) => {
		if (panel && !panel.classList.contains('hidden') && !container.contains(event.target)) {
			closeMultiselect();
		}
	});
});
