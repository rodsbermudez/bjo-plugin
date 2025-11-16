<?php
/**
 * Template para a página de Dashboard do plugin.
 *
 * @package PatropiBJO
 */

// Impede o acesso direto ao arquivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Lógica para buscar os dados do banco.
global $wpdb;

// Tabela de visitantes
$visitor_table_name = $wpdb->prefix . 'patropi_visitor_ips';
$visitor_table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $visitor_table_name ) );
if ( $visitor_table_exists === $visitor_table_name ) {
	$visitor_row_count = $wpdb->get_var( "SELECT COUNT(*) FROM `{$visitor_table_name}`" );
} else {
	$visitor_row_count = '<span class="text-danger">Tabela não encontrada</span>';
}

// Tabela de log do N8N
$n8n_log_table_name = $wpdb->prefix . 'patropi_n8n_log';
$n8n_table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $n8n_log_table_name ) );
if ( $n8n_table_exists === $n8n_log_table_name ) {
	$n8n_row_count = $wpdb->get_var( "SELECT COUNT(*) FROM `{$n8n_log_table_name}`" );
} else {
	$n8n_row_count = '<span class="text-danger">Tabela não encontrada</span>';
}

?>
<?php require_once __DIR__ . '/admin-header.php'; ?> 

<div class="col-12 mb-4">
	<p class="lead">Bem-vindo ao painel de controle do plugin de funcionalidades da Patropi Comunica. Use a navegação acima para acessar as diferentes seções.</p>
</div>

<div class="col-9">
	<div class="card">
		<div class="card-body">
			<h2 class="card-title h4">Bases de Dados</h2>
			<p class="lead mb-3">Tabelas customizadas criadas e gerenciadas por este plugin.</p>
			<div class="table-responsive">
				<table class="table table-hover">
					<thead>
						<tr>
							<th scope="col">Tabela</th>
							<th scope="col">Descrição</th>
							<th scope="col" class="text-end">Registros</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><code><?php echo esc_html( $visitor_table_name ); ?></code></td>
							<td>Armazena o IP e o país dos visitantes do site.</td>
							<td class="text-end"><?php echo is_numeric( $visitor_row_count ) ? number_format_i18n( $visitor_row_count ) : $visitor_row_count; ?></td>
						</tr>
						<tr>
							<td><code><?php echo esc_html( $n8n_log_table_name ); ?></code></td>
							<td>Registra os posts que foram criados via API pelo N8N.</td>
							<td class="text-end"><?php echo is_numeric( $n8n_row_count ) ? number_format_i18n( $n8n_row_count ) : $n8n_row_count; ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<?php require_once __DIR__ . '/admin-footer.php'; ?> 