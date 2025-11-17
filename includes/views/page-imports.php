<?php
/**
 * Template para a página de Log de Importações.
 *
 * @package PatropiBJO
 */

// Impede o acesso direto ao arquivo. 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Lógica para buscar os dados do banco.
global $wpdb;
$log_table_name = $wpdb->prefix . 'patropi_n8n_log';

// Busca todos os registros, ordenando pelos mais recentes.
$logs = $wpdb->get_results( "SELECT * FROM `{$log_table_name}` ORDER BY log_time DESC" );
 
?>
<?php require_once __DIR__ . '/admin-header.php'; ?> 

<div class="col-lg-12 mb-4">
	<div class="card">
		<div class="card-body">
			<h2 class="card-title h4">Enviar Novo Artigo</h2>
			<p class="lead mb-3">Faça o upload dos arquivos XML e PDF para iniciar a importação via N8N.</p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
				<input type="hidden" name="action" value="patropi_bjo_handle_upload">
				<?php wp_nonce_field( 'patropi_bjo_upload_nonce', 'patropi_bjo_upload_nonce_field' ); ?>
				<div class="row align-items-end">
					<div class="col-md-3 mb-3">
						<label for="xmlfile" class="form-label">Arquivo XML</label>
						<input class="form-control" type="file" name="xmlfile" id="xmlfile" accept=".xml,application/xml" required>
					</div>
					<div class="col-md-3 mb-3">
						<label for="pdffile" class="form-label">Arquivo PDF</label>
						<input class="form-control" type="file" name="pdffile" id="pdffile" accept=".pdf,application/pdf" required>
					</div>
					<div class="col-md-4 mb-3">
						<label class="form-label">Ambiente</label>
						<div class="btn-group w-100" role="group">
							<input type="radio" class="btn-check" name="n8n_environment" id="env-prod" value="production" autocomplete="off" checked>
							<label class="btn btn-outline-primary" for="env-prod">Produção</label>

							<input type="radio" class="btn-check" name="n8n_environment" id="env-test" value="test" autocomplete="off">
							<label class="btn btn-outline-primary" for="env-test">Teste</label>
						</div>
					</div>
					<div class="col-md-2 d-flex align-items-end mb-3">
						<button class="w-100 btn btn-primary" type="submit">Enviar Arquivos</button>
					</div>
				</div>
			</form>
		</div>
	</div> 
</div>

<div class="col-lg-9 mb-4 mb-lg-0">
	<div class="card h-100">
		<div class="card-body">
			<h2 class="card-title h4">Log de Importações via N8N</h2>
			<p class="lead mb-3">Histórico de todos os posts criados através da integração com o N8N.</p>
			<div class="table-responsive">
				<table class="table table-hover">
					<thead>
						<tr>
							<th scope="col" style="width: 20%;">Data da Importação</th>
							<th scope="col">Título do Post</th>
							<th scope="col" style="width: 15%;">Ações</th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $logs ) ) : ?>
							<?php foreach ( $logs as $log ) : ?>
								<?php
									// Gera os links para o post.
									$edit_link   = get_edit_post_link( $log->post_id );
									$public_link = get_permalink( $log->post_id );
								?>
								<tr>
									<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $log->log_time ) ) ); ?></td>
									<td><?php echo esc_html( $log->post_title ); ?></td>
									<td>
										<?php if ( $edit_link ) : ?>
											<a href="<?php echo esc_url( $edit_link ); ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
										<?php endif; ?>
										<?php if ( $public_link ) : ?>
											<a href="<?php echo esc_url( $public_link ); ?>" target="_blank" class="btn btn-sm btn-outline-info">Ver</a>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="3" class="text-center">Nenhum registro de importação encontrado.</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div> 
</div>

<div class="col-lg-3">
	<div class="card h-100">
		<div class="card-body">
			<h2 class="card-title h4">Detalhes da Integração</h2>
			<p class="lead mb-3">Informações sobre o workflow do N8N.</p>
			<ul class="list-group list-group-flush">
				<li class="list-group-item px-0"><strong>URL do Workflow:</strong> <a href="https://n8n-service-qz5q.onrender.com/" target="_blank" rel="noopener noreferrer">Acessar</a></li>
				<li class="list-group-item px-0"><strong>User:</strong> (a ser definido)</li>
				<li class="list-group-item px-0"><strong>Senha:</strong> (a ser definido)</li>
				<li class="list-group-item px-0"><strong>Plataforma:</strong> Render.com</li>
				<li class="list-group-item px-0"><strong>Keep alive:</strong> cron-job.com</li>
			</ul>
		</div>
	</div>
</div>

<?php require_once __DIR__ . '/admin-footer.php'; ?> 