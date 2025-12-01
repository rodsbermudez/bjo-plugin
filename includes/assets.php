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

/**
 * Enfileira os scripts e estilos para o front-end.
 */
function bjo_enqueue_frontend_assets() {
    // Enfileira o nosso novo arquivo de estilo para o front-end.
    wp_enqueue_style(
        'bjo-frontend-style', // Nome único para o nosso estilo.
        plugin_dir_url( __DIR__ ) . 'assets/css/frontend-style.css', // Caminho para o arquivo.
        array(), // Dependências (nenhuma neste caso).
        '0.0.1' // Versão do arquivo.
    );

    // Enfileira o nosso novo arquivo de script para o front-end.
    wp_enqueue_script(
        'bjo-frontend-script', // Nome único para o nosso script.
        plugin_dir_url( __DIR__ ) . 'assets/js/frontend-script.js', // Caminho para o arquivo.
        array('jquery'), // Dependências (jQuery).
        '0.0.1', // Versão do arquivo.
        true // Carrega no rodapé.
    );
}
add_action( 'wp_enqueue_scripts', 'bjo_enqueue_frontend_assets' );
