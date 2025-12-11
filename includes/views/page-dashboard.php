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

// Tabela de visualizações de artigos
$views_table_name = $wpdb->prefix . 'patropi_article_views';
$views_table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $views_table_name ) );
if ( $views_table_exists === $views_table_name ) {
	$views_row_count = $wpdb->get_var( "SELECT COUNT(*) FROM `{$views_table_name}`" );
} else {
	$views_row_count = '<span class="text-danger">Tabela não encontrada</span>';
}

// Pega o ID da página de busca avançada para verificação de status.
$advanced_search_page_id = get_option( 'bjo_advanced_search_page_id', 0 );

?>
<?php require_once __DIR__ . '/admin-header.php'; ?> 

<div class="col-12 mb-4">
	<p class="lead">Bem-vindo ao painel de controle do plugin de funcionalidades da Patropi Comunica. Use a navegação acima para acessar as diferentes seções.</p>
</div>

<div class="col-lg-6 mb-4">
	<div class="card h-100">
		<div class="card-body">
			<h2 class="card-title h4">Status do Sistema</h2>
			<p class="lead mb-3">Verificação das estruturas essenciais para o funcionamento do portal.</p>
			<table class="table table-hover">
				<thead class="table-light">
					<tr>
						<th>Estrutura</th>
						<th class="text-end">Status</th>
					</tr>
				</thead> 
				<tbody>
					<tr>
						<td>Taxonomia 'autor'</td>
						<td class="text-end"><?php echo patropi_bjo_is_taxonomy_active( 'autor' ) ? '<span class="badge bg-success">Ativa</span>' : '<span class="badge bg-danger">Inativa</span>'; ?></td>
					</tr>
					<tr>
						<td>Taxonomia 'journal'</td>
						<td class="text-end"><?php echo patropi_bjo_is_taxonomy_active( 'journal' ) ? '<span class="badge bg-success">Ativa</span>' : '<span class="badge bg-danger">Inativa</span>'; ?></td>
					</tr>
					<tr>
						<td>Taxonomia 'palavra-chave'</td>
						<td class="text-end"><?php echo patropi_bjo_is_taxonomy_active( 'palavra-chave' ) ? '<span class="badge bg-success">Ativa</span>' : '<span class="badge bg-danger">Inativa</span>'; ?></td>
					</tr>
					<tr>
						<td>Taxonomia 'keyword'</td>
						<td class="text-end"><?php echo patropi_bjo_is_taxonomy_active( 'keyword' ) ? '<span class="badge bg-success">Ativa</span>' : '<span class="badge bg-danger">Inativa</span>'; ?></td>
					</tr>
					<tr>
						<td>Taxonomia 'volume'</td>
						<td class="text-end"><?php echo patropi_bjo_is_taxonomy_active( 'volume' ) ? '<span class="badge bg-success">Ativa</span>' : '<span class="badge bg-danger">Inativa</span>'; ?></td>
					</tr>
					<tr>
						<td>Taxonomia 'tipo-do-artigo'</td>
						<td class="text-end"><?php echo patropi_bjo_is_taxonomy_active( 'tipo-do-artigo' ) ? '<span class="badge bg-success">Ativa</span>' : '<span class="badge bg-danger">Inativa</span>'; ?></td>
					</tr>
					<tr>
						<td>Taxonomia 'referencia'</td>
						<td class="text-end"><?php echo patropi_bjo_is_taxonomy_active( 'referencia' ) ? '<span class="badge bg-success">Ativa</span>' : '<span class="badge bg-danger">Inativa</span>'; ?></td>
					</tr>
				</tbody>
			</table>
			<div class="mt-3 text-center border-top pt-3">
				<p class="mb-2">Se alguma estrutura estiver inativa, importe o arquivo de configuração do ACF.</p>
				<a href="<?php echo esc_url( plugins_url( 'bjo-plugin/download/acf-import.json' ) ); ?>" class="btn btn-outline-primary" download>
					Baixar Arquivo JSON
				</a>
			</div>

			<div class="mt-4 border-top pt-3">
				<h3 class="h5">Funcionalidades do WordPress</h3>
				<table class="table table-hover">
					<thead class="table-light">
						<tr>
							<th>Funcionalidade</th>
							<th class="text-center">Status</th>
							<th class="text-end">Ações</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>Cadastro de novos membros</td>
							<td class="text-center"><?php echo get_option( 'users_can_register' ) ? '<span class="badge bg-success">Habilitado</span>' : '<span class="badge bg-danger">Desabilitado</span>'; ?></td>
							<td class="text-end"><a href="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>" class="btn btn-sm btn-outline-secondary">Gerenciar</a></td>
						</tr>
						<tr>
							<td>Filtro de Artigos</td>
							<td class="text-center"><?php echo shortcode_exists( 'bjo_filtros_artigos' ) ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-danger">Inativo</span>'; ?></td>
							<td class="text-end"><a href="<?php echo esc_url( home_url( '/artigos/' ) ); ?>" class="btn btn-sm btn-outline-secondary" target="_blank">Ver Filtros</a></td>
						</tr>
						<tr>
							<td>Busca Avançada</td>
							<td class="text-center"><?php echo ! empty( $advanced_search_page_id ) ? '<span class="badge bg-success">Ativa</span>' : '<span class="badge bg-warning">Não selecionada</span>'; ?></td>
							<td class="text-end"><a href="<?php echo esc_url( admin_url( 'admin.php?page=patropi-bjo-configs' ) ); ?>" class="btn btn-sm btn-outline-secondary">Gerenciar</a></td>
						</tr>
						<tr>
							<td>Mapa Interativo</td>
							<td class="text-center"><?php echo shortcode_exists( 'mapa_acessos' ) ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-danger">Inativo</span>'; ?></td>
							<td class="text-end"><span class="btn btn-sm btn-outline-secondary disabled" title="Visível nas páginas de artigo">N/A</span></td>
						</tr>
					</tbody>
				</table>
			</div>

			<?php
			// Pega as configurações e URLs de produção para verificação.
			$n8n_config         = patropi_bjo_get_n8n_config();
			$current_env        = get_option( 'patropi_bjo_n8n_environment', 'production' );
			$citations_prod_url = $n8n_config['webhooks']['citations']['production'] ?? '';
			$import_prod_url    = $n8n_config['webhooks']['xml_import']['production'] ?? '';
			$api_key            = $n8n_config['api_key'] ?? '';

			// Verifica o status dos webhooks de produção.
			$citations_status = patropi_bjo_check_webhook_status( $citations_prod_url, $api_key );
			$import_status    = patropi_bjo_check_webhook_status( $import_prod_url, $api_key );
			?>
			<div class="mt-4 border-top pt-3">
				<h3 class="h5">Workflow N8N</h3>
				<table class="table table-hover mb-0">
					<thead class="table-light">
						<tr>
							<th>Funcionalidade</th>
							<th class="text-end">Status</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>Ambiente Global</td>
							<td class="text-end"><span class="badge bg-<?php echo 'production' === $current_env ? 'success' : 'warning'; ?>"><?php echo esc_html( ucfirst( $current_env ) ); ?></span></td>
						</tr>
						<tr>
							<td>Workflow de Importação</td>
							<td class="text-end"><span class="badge bg-<?php echo $import_status ? 'success' : 'danger'; ?>"><?php echo $import_status ? 'Ativo' : 'Inativo'; ?></span></td>
						</tr>
						<tr>
							<td>Workflow de Citações</td>
							<td class="text-end"><span class="badge bg-<?php echo $citations_status ? 'success' : 'danger'; ?>"><?php echo $citations_status ? 'Ativo' : 'Inativo'; ?></span></td>
						</tr>
					</tbody>
				</table>
			</div> 
            
		</div>
	</div> 
</div>
<div class="col-lg-6 mb-4">
	<div class="card">
		<div class="card-body">
			<h2 class="card-title h4">Bases de Dados</h2>
			<p class="lead mb-3">Tabelas customizadas criadas e gerenciadas por este plugin.</p>
			<p><strong>Nome do Banco de Dados:</strong> <code><?php echo DB_NAME; ?></code></p>
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
						<tr>
							<td><code><?php echo esc_html( $views_table_name ); ?></code></td>
							<td>Armazena os acessos (visualizações) de cada artigo.</td>
							<td class="text-end"><?php echo is_numeric( $views_row_count ) ? number_format_i18n( $views_row_count ) : $views_row_count; ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<div class="col-12 mb-4">
	<div class="card">
		<div class="card-body">
			<h2 class="card-title h4">Queries Customizadas</h2>
			<p class="lead mb-3">Lista de queries customizadas disponíveis para uso no bloco "Query Loop" do editor de páginas.</p>
			<div class="table-responsive">
				<table class="table table-hover mb-0">
					<thead class="table-light">
						<tr>
							<th scope="col" style="width: 20%;">ID da Query</th>
							<th scope="col">Descrição</th>
							<th scope="col" style="width: 25%;">Local de Uso</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><code>filtro-artigos</code></td>
							<td>Filtra a lista de posts (artigos) com base nos parâmetros de taxonomia enviados pela URL (ex: <code>/artigos/?filter_autor[]=123</code>). Utilizada em conjunto com o shortcode <code>[bjo_filtros_artigos]</code>.</td>
							<td>Página de listagem de Artigos (<code>/artigos/</code>).</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<?php require_once __DIR__ . '/admin-footer.php'; ?> 