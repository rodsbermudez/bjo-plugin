<?php
/**
 * Funcionalidades do menu administrativo do plugin.
 *
 * @package PatropiBJO
 */

// Impede o acesso direto ao arquivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adiciona o menu e submenus no painel administrativo.
 */
function patropi_bjo_admin_menu() {
	/**
	 * Cria o item de menu principal (de nível superior).
	 * @see https://developer.wordpress.org/reference/functions/add_menu_page/
	 */
	// Gera a URL para o ícone customizado.
	$icon_url = plugin_dir_url( __DIR__ ) . 'assets/images/patropi-favicon.png';

	add_menu_page(
		'Patropi Comunica - Dashboard',    // 1. Título da página (o que aparece na tag <title> do navegador).
		'Patropi Comunica',                // 2. Título do menu (o que aparece na barra lateral do admin).
		'manage_options',                  // 3. Capacidade: quem pode ver este menu (apenas administradores).
		'patropi-bjo-dashboard',           // 4. Slug do menu: um identificador único para este menu.
		'patropi_bjo_dashboard_page_html', // 5. Função de callback para a página principal.
		$icon_url,                         // 6. Ícone: o ícone que aparece ao lado do título do menu.
		2                                  // 7. Posição: onde o menu aparece na barra lateral (números menores ficam mais acima).
	);

	/**
	 * Cria o primeiro submenu: "Dashboard".
	 * Este será a página padrão quando alguém clicar no menu principal.
	 * @see https://developer.wordpress.org/reference/functions/add_submenu_page/
	 */
	add_submenu_page(
		'patropi-bjo-dashboard',           // 1. Slug do menu pai: ao qual este submenu pertence.
		'Dashboard',                       // 2. Título da página.
		'Dashboard',                       // 3. Título do submenu (o que aparece na lista).
		'manage_options',                  // 4. Capacidade.
		'patropi-bjo-dashboard',           // 5. Slug do submenu: usamos o mesmo slug do pai para torná-lo a página padrão.
		'patropi_bjo_dashboard_page_html'  // 6. Função de callback: a função que gera o HTML desta página.
	);

	/**
	 * Cria o submenu "Importações".
	 */
	add_submenu_page(
		'patropi-bjo-dashboard',           // 1. Slug do menu pai.
		'Importações',                     // 2. Título da página.
		'Importações',                     // 3. Título do submenu.
		'manage_options',                  // 4. Capacidade.
		'patropi-bjo-imports',             // 5. Slug do submenu.
		'patropi_bjo_imports_page_html'    // 6. Função de callback.
	);

	/**
	 * Cria o submenu "Atualizações".
	 */
	add_submenu_page(
		'patropi-bjo-dashboard',           // 1. Slug do menu pai.
		'Atualizações',                    // 2. Título da página.
		'Atualizações',                    // 3. Título do submenu (o que aparece na lista).
		'manage_options',                  // 4. Capacidade.
		'patropi-bjo-updates',             // 5. Slug do submenu.
		'patropi_bjo_updates_page_html'    // 6. Função de callback: a função que gera o HTML desta página.
	);

	/**
	 * Cria o segundo submenu: "Suporte".
	 */
	add_submenu_page(
		'patropi-bjo-dashboard',           // 1. Slug do menu pai.
		'Suporte técnico',                 // 2. Título da página.
		'Suporte',                         // 3. Título do submenu.
		'manage_options',                  // 4. Capacidade.
		'patropi-bjo-contact',             // 5. Slug do submenu: um identificador único para esta página.
		'patropi_bjo_contact_page_html'    // 6. Função de callback.
	);
}
// "Engata" nossa função `patropi_bjo_admin_menu` no hook `admin_menu` do WordPress.
// Isso garante que nossa função será executada no momento certo em que o WordPress está construindo o menu do painel.
add_action( 'admin_menu', 'patropi_bjo_admin_menu' );

/**
 * Renderiza o HTML para a página de "Dashboard".
 */
function patropi_bjo_dashboard_page_html() {
	// Verifica se o usuário tem permissão para acessar a página.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	// Carrega o arquivo de template da página.
	require_once plugin_dir_path( __DIR__ ) . 'includes/views/page-dashboard.php';
}

/**
 * Renderiza o HTML para a página de "Importações".
 */
function patropi_bjo_imports_page_html() {
	// Verifica se o usuário tem permissão para acessar a página.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	// Carrega o arquivo de template da página.
	require_once plugin_dir_path( __DIR__ ) . 'includes/views/page-imports.php';
}

/**
 * Renderiza o HTML para a página de "Atualizações".
 * Esta é a função chamada como callback no primeiro `add_submenu_page`.
 */
function patropi_bjo_updates_page_html() {
	// Verifica se o usuário tem permissão para acessar a página.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	// Carrega o arquivo de template da página.
	require_once plugin_dir_path( __DIR__ ) . 'includes/views/page-updates.php';
}

/**
 * Renderiza o HTML para a página "Suporte".
 * Esta é a função chamada como callback no segundo `add_submenu_page`.
 */
function patropi_bjo_contact_page_html() {
	// Verifica se o usuário tem permissão para acessar a página.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	// Carrega o arquivo de template da página.
	require_once plugin_dir_path( __DIR__ ) . 'includes/views/page-contact.php';
}