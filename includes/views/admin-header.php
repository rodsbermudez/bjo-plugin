<?php
/**
 * Cabeçalho para as páginas de administração do plugin.
 *
 * @package PatropiBJO
 */ 

// Impede o acesso direto ao arquivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Pega o slug da página atual para marcar o menu como ativo.
$current_page_slug = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
?>

<div class="wrap patropi-bjo-admin-page container-fluid py-4">
	<div class="mb-4">
		<img src="<?php echo esc_url( plugins_url( 'bjo-plugin/assets/images/patropi-logo.png' ) ); ?>" alt="Logo Patropi Comunica" style="max-width: 250px; height: auto;">
	</div>

	<div class="btn-group mb-4" role="group" aria-label="Navegação principal do plugin">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=patropi-bjo-dashboard' ) ); ?>" class="btn <?php echo ( 'patropi-bjo-dashboard' === $current_page_slug ) ? 'btn-primary' : 'btn-outline-primary'; ?>">
			Dashboard
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=patropi-bjo-imports' ) ); ?>" class="btn <?php echo ( 'patropi-bjo-imports' === $current_page_slug ) ? 'btn-primary' : 'btn-outline-primary'; ?>">
			Importações
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=patropi-bjo-updates' ) ); ?>" class="btn <?php echo ( 'patropi-bjo-updates' === $current_page_slug || ! isset( $_GET['page'] ) ) ? 'btn-primary' : 'btn-outline-primary'; ?>">
			Atualizações
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=patropi-bjo-contact' ) ); ?>" class="btn <?php echo ( 'patropi-bjo-contact' === $current_page_slug ) ? 'btn-primary' : 'btn-outline-primary'; ?>">
			Suporte
		</a>
	</div>

	<!-- A grade de conteúdo começa aqui -->
	<div class="row">