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
