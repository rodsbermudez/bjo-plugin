<?php
/**
 * Template para a página de Atualizações do plugin.
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
			<div class="card p-4">
				<h1 class="border-bottom pb-3 mb-4"><?php echo esc_html( get_admin_page_title() ); ?></h1>

				<h2 class="h4">Funcionamento</h2>
				<p>Abaixo estão as funcionalidades ativas neste plugin:</p>
				<ul class="list-group mb-4">
					<li class="list-group-item"><strong>Integração com N8N:</strong> Permite que o N8N envie dados via API REST para criar e popular termos da taxonomia 'journal' com campos ACF.</li>
				</ul>

				<h2 class="h4">Versões</h2>
				<p class="mb-0"><strong>0.0.1:</strong> Setup inicial, criação de páginas administrativas, verificação de dependências, integração com N8N e refatoração da estrutura de arquivos.</p>
			</div>
		</div>
	</div>
</div>
