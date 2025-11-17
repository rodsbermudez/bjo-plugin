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

	// Verifica se a página atual é uma das páginas do nosso plugin.
	if ( isset( $_GET['page'] ) && in_array( $_GET['page'], $plugin_page_slugs, true ) ) {
		// Enfileira o CSS do Bootstrap (tema Flatly) a partir de um CDN.
		wp_enqueue_style( 'patropi-bjo-bootstrap-flatly', 'https://cdn.jsdelivr.net/npm/bootswatch@5.3.2/dist/flatly/bootstrap.min.css', array(), '5.3.2' );

		// Enfileira nosso CSS customizado.
		wp_enqueue_style(
			'patropi-bjo-admin-style',
			plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/admin-style.css',
			array( 'patropi-bjo-bootstrap-flatly' ), // Dependência: carrega DEPOIS do Bootstrap.
			'0.0.3' // Versão do plugin.
		);
	}
}
add_action( 'admin_enqueue_scripts', 'patropi_bjo_admin_enqueue_assets' );