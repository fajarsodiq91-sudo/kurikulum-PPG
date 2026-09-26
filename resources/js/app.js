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
