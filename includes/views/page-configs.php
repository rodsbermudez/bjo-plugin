<?php 
/**
 * Template para a página de Configurações do plugin.
 *
 * @package PatropiBJO
 */

// Impede o acesso direto ao arquivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} 

// Pega as opções atuais do banco de dados.
$advanced_search_page_id = get_option( 'bjo_advanced_search_page_id', 0 );
$google_translate_key    = get_option( 'patropi_bjo_google_translate_key', '' );

// Prepara a chave mascarada para exibição.
$masked_key = $google_translate_key;
if ( ! empty( $google_translate_key ) && strlen( $google_translate_key ) > 10 ) {
	$masked_key = substr( $google_translate_key, 0, 5 ) . str_repeat( '*', 20 ) . substr( $google_translate_key, -5 );
}

// Processa o salvamento do formulário.
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['patropi_bjo_save_configs'] ) ) {
	if ( check_admin_referer( 'patropi_bjo_save_configs_action', 'patropi_bjo_configs_nonce' ) ) {
		if ( isset( $_POST['advanced_search_page_id'] ) ) {
			$advanced_search_page_id = sanitize_text_field( $_POST['advanced_search_page_id'] );
			update_option( 'bjo_advanced_search_page_id', $advanced_search_page_id );
		}
		if ( isset( $_POST['google_translate_key'] ) ) {
			$posted_key = sanitize_text_field( $_POST['google_translate_key'] );
			// Só atualiza se o valor enviado for diferente da máscara (indicando que o usuário digitou uma nova chave).
			if ( $posted_key !== $masked_key ) {
				update_option( 'patropi_bjo_google_translate_key', $posted_key );
				// Atualiza a máscara para exibir a nova chave imediatamente.
				$masked_key = substr( $posted_key, 0, 5 ) . str_repeat( '*', 20 ) . substr( $posted_key, -5 );
			}
		}
		// Define a flag para exibir a mensagem de sucesso (sem redirecionamento para evitar tela branca).
		$_GET['settings-updated'] = 'true';
	}
}
 
?>
<?php require_once __DIR__ . '/admin-header.php'; ?>

<form method="post">
	<?php wp_nonce_field( 'patropi_bjo_save_configs_action', 'patropi_bjo_configs_nonce' ); ?>

	<div class="col-12 mb-4">
		<div class="card">
			<div class="card-body">
				<h2 class="card-title h4">Configurações Gerais</h2>
				<p class="lead mb-4">Ajustes e configurações para as funcionalidades do plugin. 
				<?php
				// Exibe a mensagem de "Configurações salvas."
				if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) {
					echo '<span class="ms-2 badge bg-success">Configurações salvas com sucesso!</span>';
				}
				?>
				</p>

				<div class="row">
					<div class="col-md-6">
						<div class="mb-3">
							<label for="advanced_search_page_id" class="form-label"><strong>Página de Busca Avançada</strong></label>
							<p class="form-text">Selecione a página que será usada para a busca avançada de artigos. O link para esta página aparecerá no formulário de filtros.</p>
							<?php
							wp_dropdown_pages( array(
								'name'              => 'advanced_search_page_id',
								'show_option_none'  => '— Nenhuma —',
								'option_none_value' => '0',
								'selected'          => $advanced_search_page_id,
								'class'             => 'form-select',
							) );
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-12 mb-4">
		<div class="card">
			<div class="card-body">
				<h2 class="card-title h4">Configurar integrações</h2>
				<div class="row">
					<div class="col-md-6">
						<div class="mb-3">
							<label for="google_translate_key" class="form-label"><strong>Chave da API do Google Translate</strong></label>
							<input type="text" class="form-control" id="google_translate_key" name="google_translate_key" value="<?php echo esc_attr( $masked_key ); ?>" placeholder="Cole sua chave de API aqui para alterar">
							<div class="form-text">
								Necessária para a busca bilíngue (PT/EN). O sistema traduzirá o termo buscado automaticamente. <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Obter chave no Google Cloud</a>.
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-12">
		<button type="submit" name="patropi_bjo_save_configs" class="btn btn-primary">Salvar Configurações</button>
	</div>
</form>

<?php require_once __DIR__ . '/admin-footer.php'; ?>