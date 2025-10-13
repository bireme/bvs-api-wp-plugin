=== BVSalud Integrator ===
Contributors: bireme
Tags: bvs, journals, health, api
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin para integração com a API BVS Saúde, permitindo exibir journals e recursos web.

== Description ==

O BVSalud Integrator permite integrar conteúdo da API BVS Saúde diretamente em seu site WordPress através de shortcodes personalizáveis.

= Funcionalidades =

* Exibir journals da BVS
* Exibir recursos web da BVS
* Múltiplos filtros de busca
* Templates responsivos
* Paginação opcional
* Totalmente customizável

= Shortcodes Disponíveis =

**[bvs_journals]** - Exibe journals da BVS Saúde

Parâmetros:
* `country` - Filtrar por país
* `subject` - Filtrar por área temática
* `search` - Busca livre
* `searchTitle` - Buscar por título específico
* `issn` - Buscar por ISSN
* `limit` - Itens por página (padrão: 12)
* `max` - Máximo de itens (padrão: 50)
* `show_pagination` - Habilitar paginação (true/false)
* `template` - Layout (default, compact, detailed, grid)
* `columns` - Número de colunas (padrão: 4)

**[bvs_web_resources]** - Exibe recursos web da BVS

Parâmetros:
* `country` - Filtrar por país
* `subject` - Filtrar por assunto
* `type` - Filtrar por tipo
* `term` - Buscar por termo
* `searchTitle` - Buscar por título específico
* `count` - Número de resultados (padrão: 10)
* `template` - Template a usar
* `show_description` - Mostrar descrição (true/false)
* `show_type` - Mostrar tipo (true/false)
* `show_country` - Mostrar país (true/false)
* `show_languages` - Mostrar idiomas (true/false)
* `show_publisher` - Mostrar editora (true/false)

== Installation ==

1. Faça upload do plugin para o diretório `/wp-content/plugins/`
2. Ative o plugin através do menu 'Plugins' no WordPress
3. Configure a API URL e Token em 'BVSalud' no menu administrativo
4. Use os shortcodes em suas páginas e posts

== Frequently Asked Questions ==

= Como obter um token da API? =

Entre em contato com a equipe BIREME para obter credenciais de acesso à API.

= Posso customizar o visual dos cards? =

Sim, você pode adicionar CSS customizado usando as classes disponíveis nos templates.

= Há limite de requisições à API? =

Sim, respeite os limites de requisição definidos pela API BVS.

== Exemplos de Uso ==

Journals do Brasil:
`[bvs_journals country="Brasil" max="20"]`

Busca por título:
`[bvs_journals searchTitle="saúde pública" limit="10"]`

Filtros combinados (AND) - Journals de Medicina do Brasil:
`[bvs_journals country="Brasil" subject="Medicina" max="30"]`

Recursos web por assunto:
`[bvs_web_resources subject="Medicina" count="15"]`

Busca por título em recursos web:
`[bvs_web_resources searchTitle="biblioteca virtual" count="8"]`

Filtros combinados (AND) - Bases de dados do Brasil sobre Medicina:
`[bvs_web_resources type="database" country="Brazil" subject="Medicina"]`

**IMPORTANTE:** Múltiplos filtros funcionam como AND (todos devem ser verdadeiros).

== Parâmetros via URL ==

Ambos os shortcodes aceitam parâmetros através da URL (query string), permitindo criar buscas dinâmicas.

**Exemplo:** Coloque `[bvs_journals]` em uma página e acesse:
* `/journals/?bvsCountry=Brasil` - Busca journals do Brasil
* `/journals/?bvsTitle=medicina&bvsLimit=20` - Busca por título com 20 resultados
* `/journals/?bvsSubject=Enfermagem&bvsPage=2` - Página 2 de journals de Enfermagem

**Importante:** Parâmetros da URL têm prioridade sobre os do shortcode.

**Parâmetros disponíveis:**
* `bvsCountry` - País
* `bvsSubject` - Assunto/área temática
* `bvsSearch` - Busca livre (journals)
* `bvsSearchTitle` ou `bvsTitle` - Buscar por título
* `bvsIssn` - ISSN (journals)
* `bvsType` - Tipo de recurso (web resources)
* `bvsTerm` - Termo de busca (web resources)
* `bvsLimit` - Itens por página
* `bvsMax` - Máximo de itens total (journals)
* `bvsCount` - Número de resultados (web resources)
* `bvsTemplate` - Template de exibição
* `bvsColumns` - Número de colunas (grid)
* `bvsPage` - Página atual

Veja mais exemplos no arquivo URL_PARAMETERS_USAGE.md

== Changelog ==

= 1.0.0 =
* Versão inicial
* Shortcode [bvs_journals]
* Shortcode [bvs_web_resources]
* Filtros por país, assunto, tipo, título, ISSN
* Busca por título (searchTitle)
* **Filtros combinados com AND** - múltiplos filtros podem ser usados simultaneamente
* Parâmetros via URL com prioridade sobre shortcode
* Templates responsivos
* Sistema de paginação
* Sistema de cache

