<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php require_once __DIR__ . '/admin-header.php'; ?>

<div class="col-lg-6 mb-4">
	<div class="card h-100">
		<div class="card-body">
			<h2 class="card-title h4">Funcionalidades Ativas</h2>
			<p class="lead mb-3">Recursos que este plugin adiciona ao seu site.</p>
			<table class="table table-hover">
				<thead class="table-light">
					<tr>
						<th scope="col" style="width: 25%;">Funcionalidade</th>
						<th scope="col">Descrição</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><strong>Importador de Artigos</strong></td>
						<td>Adiciona um formulário para upload de arquivos XML e PDF, processa os arquivos e os envia para o N8N para criação de posts.</td>
					</tr>
				</tbody>
				<tbody>
					<tr>
						<td><strong>Busca de Citações</strong></td>
						<td>Integração com a API da <a href="https://www.semanticscholar.org/" target="_blank">semanticscholar.org</a> (via N8N) para encontrar e exibir artigos que citam os posts do site através do shortcode <code>[enviar_referencias_n8n]</code>.</td>
					</tr>
					<tr>
						<td><strong>Shortcodes Dinâmicos</strong></td>
						<td>Ferramentas como <code>[listar_termos]</code> para exibir listas de taxonomias de forma flexível no front-end.</td>
					</tr>
					<tr>
						<td><strong>Gerenciamento de Ambiente</strong></td>
						<td>Permite alternar entre os ambientes de 'Produção' e 'Teste' para as integrações com o N8N. Inclui um monitor de status dos workflows no dashboard.</td>
					</tr>
					<tr>
						<td><strong>Contador de Acessos</strong></td>
						<td>Registra as visualizações de cada artigo, salvando o IP, ID do usuário (se logado) e a data do acesso.</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>

<div class="col-lg-6 mb-4"> 
	<div class="card h-100"> 
		<div class="card-body">
			<h2 class="card-title h4">Histórico de Versões</h2>
			<p class="lead mb-3">Acompanhe as novidades e correções de cada versão.</p>
			<table class="table table-hover mb-0">
				<tbody>
					<tr>
						<td style="width: 15%;"><strong>0.0.11</strong></td>
						<td>Adiciona verificação para as taxonomias 'palavra-chave' e 'tipo-do-artigo' no 'Status do Sistema'.</td>
					</tr>
					<tr>
						<td style="width: 15%;"><strong>0.0.10</strong></td>
						<td>Adiciona paginação à lista de logs de importação na página 'Importações' e aplica o estilo do Bootstrap.</td>
					</tr>
					<tr>
						<td style="width: 15%;"><strong>0.0.9</strong></td>
						<td>Adiciona um sistema de rastreamento de visualizações para os artigos, com uma nova tabela no banco de dados e exibição no dashboard.</td>
					</tr>
					<tr>
						<td style="width: 15%;"><strong>0.0.8</strong></td>
						<td>Adiciona um seletor de ambiente (Produção/Teste) para as integrações com o N8N e um painel de status dos workflows no dashboard. Centraliza as chaves de API e URLs em um arquivo de configuração.</td>
					</tr>
					<tr>
						<td style="width: 15%;"><strong>0.0.7</strong></td>
						<td>Adiciona shortcode para busca de citações no Semantic Scholar via N8N e shortcode para listagem de termos de taxonomias.</td>
					</tr>
					<tr>
						<td style="width: 15%;"><strong>0.0.6</strong></td>
						<td>Adiciona um painel de 'Status do Sistema' no dashboard para verificar a ativação de taxonomias e configurações do WordPress. Inclui um botão para download do arquivo de importação do ACF.</td>
					</tr>
					<tr>
						<td style="width: 15%;"><strong>0.0.5</strong></td>
						<td>Adiciona um painel de 'Status do Sistema' no dashboard para verificar a ativação de taxonomias e configurações do WordPress.</td>
					</tr>
					<tr>
						<td style="width: 15%;"><strong>0.0.4</strong></td>
						<td>Adiciona um painel de 'Status do Sistema' no dashboard para verificar a ativação de taxonomias e configurações do WordPress.</td>
					</tr>
					<tr>
						<td style="width: 15%;"><strong>0.0.3</strong></td>
						<td>Adiciona um painel de 'Status do Sistema' no dashboard para verificar a ativação de taxonomias e configurações do WordPress.</td>
					</tr>
					<tr>
						<td style="width: 15%;"><strong>0.0.2</strong></td>
						<td>Adiciona um painel de 'Status do Sistema' no dashboard para verificar a ativação de taxonomias e configurações do WordPress.</td>
					</tr>
					<tr>
						<td style="width: 15%;"><strong>0.0.1</strong></td>
						<td>Versão inicial do plugin.</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>

<?php require_once __DIR__ . '/admin-footer.php'; ?>
