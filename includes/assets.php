<?php
/**
 * Gerenciamento de assets (CSS, JS) do plugin.
 *
 * @package PatropiBJO
 */

// Impede o acesso direto ao arquivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enfileira os scripts e estilos para o painel de administração.
 *
 * @param string $hook_suffix O hook da página atual do admin.
 */
function patropi_bjo_admin_enqueue_assets( $hook_suffix ) {
	// Lista de slugs das nossas páginas de admin.
	$plugin_page_slugs = array(
		'patropi-bjo-dashboard',
		'patropi-bjo-updates',
		'patropi-bjo-imports',
		'patropi-bjo-contact',
	);

	// Enfileira nosso CSS customizado em todas as páginas do admin para corrigir o ícone do menu.
	wp_enqueue_style(
		'patropi-bjo-admin-style',
		plugin_dir_url( __DIR__ ) . 'assets/css/admin-style.css',
		array(), // Dependências.
		'0.0.6' // Versão do arquivo.
	);

	// Verifica se a página atual é uma das páginas do nosso plugin.
	if ( isset( $_GET['page'] ) && in_array( $_GET['page'], $plugin_page_slugs, true ) ) {
		// Enfileira o CSS do Bootstrap (tema Flatly) a partir de um CDN.
		wp_enqueue_style( 'patropi-bjo-bootstrap-flatly', 'https://cdn.jsdelivr.net/npm/bootswatch@5.3.2/dist/flatly/bootstrap.min.css', array(), '5.3.2' );
	}
}
add_action( 'admin_enqueue_scripts', 'patropi_bjo_admin_enqueue_assets' );