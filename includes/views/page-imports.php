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

// Configurações da paginação.
$per_page     = 25;
$current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
$offset       = ( $current_page - 1 ) * $per_page;

// Busca o total de registros para calcular o número de páginas.
$total_items = $wpdb->get_var( "SELECT COUNT(*) FROM `{$log_table_name}`" );
$total_pages = ceil( $total_items / $per_page );

// Busca os registros da página atual, ordenando pelos mais recentes.
$logs = $wpdb->get_results( $wpdb->prepare(
	"SELECT * FROM `{$log_table_name}` ORDER BY log_time DESC LIMIT %d OFFSET %d",
	$per_page,
	$offset
) );
 
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
					<div class="col-md-5 mb-3">
						<label for="xmlfile" class="form-label">Arquivo XML</label>
						<input class="form-control" type="file" name="xmlfile" id="xmlfile" accept=".xml,application/xml" required>
					</div>
					<div class="col-md-5 mb-3">
						<label for="pdffile" class="form-label">Arquivo PDF</label>
						<input class="form-control" type="file" name="pdffile" id="pdffile" accept=".pdf,application/pdf" required>
					</div>
					<div class="col-md-2 d-flex align-items-end mb-3">
						<button class="w-100 btn btn-primary" type="submit">Enviar Arquivos</button>
					</div>
				</div>
			</form>
		</div>
	</div> 
</div>

<div class="col-lg-8 mb-4 mb-lg-0">
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
						<?php if ( ! empty( $logs ) ) : ?>~
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
			<?php if ( $total_pages > 1 ) : ?>
				<div class="d-flex justify-content-between align-items-center mt-3">
					<p class="mb-0 text-muted">
						<?php echo esc_html( number_format_i18n( $total_items ) ); ?> itens no total / 25 por página
					</p>
					<nav aria-label="Paginação dos logs">
						<ul class="pagination pagination-sm mb-0">
							<?php if ( $current_page > 1 ) : ?>
								<li class="page-item">
									<a class="page-link" href="<?php echo esc_url( add_query_arg( 'paged', $current_page - 1 ) ); ?>">Anterior</a>
								</li>
							<?php endif; ?>

							<?php for ( $i = 1; $i <= $total_pages; $i++ ) : ?>
								<li class="page-item <?php echo ( $i === $current_page ) ? 'active' : ''; ?>">
									<a class="page-link" href="<?php echo esc_url( add_query_arg( 'paged', $i ) ); ?>"><?php echo esc_html( $i ); ?></a>
								</li>
							<?php endfor; ?>

							<?php if ( $current_page < $total_pages ) : ?>
								<li class="page-item">
									<a class="page-link" href="<?php echo esc_url( add_query_arg( 'paged', $current_page + 1 ) ); ?>">Próximo</a>
								</li>
							<?php endif; ?>
						</ul>
					</nav>
				</div>
			<?php endif; ?>
		</div>
	</div> 
</div>

<div class="col-lg-4">
	<div class="card h-100">
		<div class="card-body">
			<h2 class="card-title h4">Detalhes da Integração</h2>
			<p class="lead mb-3">Configurações e informações sobre o N8N.</p>
 
			<?php
			// Pega o ambiente atual salvo no banco de dados.
			$current_env = get_option( 'patropi_bjo_n8n_environment', 'production' ); 
			?>
			<ul class="list-group list-group-flush mb-4">
				<li class="list-group-item px-0"><strong>URL do Workflow:</strong> <a href="https://n8n-service-qz5q.onrender.com/" target="_blank" rel="noopener noreferrer">Acessar</a></li>
				<li class="list-group-item px-0"><strong>Plataforma:</strong> Render.com</li>
				<li class="list-group-item px-0"><strong>Keep alive:</strong> cron-job.com</li>
			</ul>

			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="mb-4">
				<input type="hidden" name="action" value="patropi_bjo_save_n8n_environment">
				<?php wp_nonce_field( 'patropi_bjo_save_n8n_env', 'patropi_bjo_n8n_env_nonce' ); ?>
				<label class="form-label"><strong>Ambiente Global de Integração</strong></label>
				<div class="btn-group w-100" role="group">
					<input type="radio" class="btn-check" name="n8n_global_environment" id="global-env-prod" value="production" autocomplete="off" <?php checked( 'production', $current_env ); ?>>
					<label class="btn btn-outline-primary" for="global-env-prod">Produção</label>

					<input type="radio" class="btn-check" name="n8n_global_environment" id="global-env-test" value="test" autocomplete="off" <?php checked( 'test', $current_env ); ?>>
					<label class="btn btn-outline-primary" for="global-env-test">Teste</label>
				</div>
				<button type="submit" class="btn btn-primary w-100 mt-2">Salvar Ambiente</button>
			</form>
		</div>
	</div>
</div>

	</div><!-- .row -->
</div><!-- .wrap -->