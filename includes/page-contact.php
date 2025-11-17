<?php
/**
 * Template para a página de Suporte do plugin.
 *
 * @package PatropiBJO
 */

// Impede o acesso direto ao arquivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap patropi-bjo-admin-page container-fluid py-4">
	<div class="row">
		<div class="col-12">
			<h1 class="mb-4">Patropi Comunica</h1>
			<div class="card p-4">
				<?php
				// Pega o slug da página atual para marcar o menu como ativo.
				$current_page_slug = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
				?>
				<ul class="nav nav-tabs mb-4">
					<li class="nav-item">
						<a class="nav-link <?php echo ( 'patropi-bjo-updates' === $current_page_slug ) ? 'active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=patropi-bjo-updates' ) ); ?>">
							Atualizações
						</a>
					</li>
					<li class="nav-item">
						<a class="nav-link <?php echo ( 'patropi-bjo-contact' === $current_page_slug ) ? 'active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=patropi-bjo-contact' ) ); ?>">
							Suporte
						</a>
					</li>
				</ul>

				<p class="lead">Precisa de ajuda ou quer saber mais sobre nosso trabalho?</p>
				<ul class="list-group">
					<li class="list-group-item"><strong>Site:</strong> <a href="https://patropicomunica.com.br" target="_blank">patropicomunica.com.br</a></li>
					<li class="list-group-item"><strong>Autor:</strong> Rodrigo Mermudez</li>
				</ul>
			</div>
		</div>
	</div>
</div>
