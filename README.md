# BVSalud Integrator - WordPress Plugin

Plugin WordPress para integração com a API BVS Saúde, permitindo exibir journals e recursos web através de shortcodes personalizáveis.

## 📋 Índice

- [Características](#-características)
- [Instalação](#-instalação)
- [Configuração](#-configuração)
- [Shortcodes](#-shortcodes)
  - [Parâmetros via URL](#parâmetros-via-url-query-string)
- [API Client](#-api-client)
- [Desenvolvimento](#-desenvolvimento)

## ✨ Características

- 🔍 **Busca por múltiplos filtros**: país, assunto, tipo, título, ISSN
- 🎛️ **Sidebar de Filtros**: Interface visual com checkboxes para múltiplos países
- 📱 **Layout responsivo**: grid de cards adaptável
- 📄 **Paginação integrada**: navegação fácil entre resultados
- 🎨 **Customizável**: CSS flexível
- ⚡ **Sistema de cache**: otimização de performance
- 🔒 **Seguro**: sanitização de inputs e escape de outputs

## 📦 Instalação

1. Clone ou faça download do repositório para `/wp-content/plugins/`
2. Ative o plugin no WordPress
3. Configure a API URL e Token em **BVSalud > Configurações**
''
```bash
cd wp-content/plugins
git clone [repository-url] api-consumer-wp-plugin
```

## ⚙️ Configuração

Após ativar o plugin, vá para **BVSalud > Configurações** e configure:

- **API URL**: URL base da API BVS (ex: `https://api.bvsalud.org/v1`)
- **API Token**: Token de autenticação fornecido pela BIREME

## 🎯 Shortcodes

> **💡 Dica:** Todos os shortcodes aceitam parâmetros via URL (query string), permitindo criar buscas dinâmicas. Os parâmetros da URL **sobrescrevem** os do shortcode.

> **🔗 Filtros Combinados:** Quando múltiplos filtros são fornecidos, eles funcionam como **AND** (todos devem ser verdadeiros).

### Shortcodes Disponíveis

| Shortcode | Descrição | Status |
|-----------|-----------|--------|
| `[bvs_journals]` | Periódicos científicos | ✅ Completo |
| `[bvs_web_resources]` | Recursos web (LIS) | ✅ Completo |
| `[bvs_legislations]` | Leis, decretos, normas | ✅ Completo |
| `[bvs_databases]` ou `[bvs_bibliographic_databases]` | Bases bibliográficas | ✅ Completo |
| `[bvs_events]` | Eventos em saúde | ⚠️ API Incorreta |
| `[bvs_multimedia]` | Vídeos, áudios, imagens | ⚠️ API Incorreta |

**Nota:** Os shortcodes `[bvs_events]` e `[bvs_multimedia]` estão implementados mas apresentam problemas na chamada da API, retornando erros de conexão.

### [bvs_journals]

Exibe journals da BVS Saúde com diversos filtros e opções de visualização.

#### Parâmetros de Filtragem

| Parâmetro | Descrição | Exemplo |
|-----------|-----------|---------|
| `country` | Filtrar por país | `country="Brasil"` |
| `subject` | Filtrar por área temática | `subject="Medicina"` |
| `search` | Busca livre (fulltext) | `search="cardiologia"` |
| `searchTitle` | Buscar por título específico | `searchTitle="saúde pública"` |
| `issn` | Buscar por ISSN | `issn="1234-5678"` |

#### Parâmetros de Configuração

| Parâmetro | Descrição | Padrão |
|-----------|-----------|--------|
| `limit` | Itens por página | `12` |
| `max` | Máximo de itens total | `50` |
| `show_pagination` | Habilitar paginação | `false` |
| `page` | Página inicial | `1` |
| `columns` | Número de colunas | `3` |
| `show_fields` | Campos a exibir | `title,issn,publisher,country` |
| `showFilters` | Mostrar sidebar de filtros | `false` |

**Nota:** Todos os shortcodes utilizam o layout grid (grade de cards responsiva).

#### Exemplos de Uso

```php
// Grid básico com journals do Brasil
[bvs_journals country="Brasil" max="20"]

// Com sidebar de filtros interativos (checkboxes de países)
[bvs_journals showFilters="true"]

// Grid com paginação (sempre 3 colunas)
[bvs_journals limit="12" show_pagination="true"]

// Busca por título
[bvs_journals searchTitle="saúde pública" limit="15"]

// Busca por ISSN específico
[bvs_journals issn="1234-5678"]

// Grid com filtros ativos (sempre 3 colunas)
[bvs_journals country="Argentina" max="30" showFilters="true"]

// FILTROS COMBINADOS (AND)
// Journals do Brasil na área de Medicina
[bvs_journals country="Brasil" subject="Medicina" max="30"]

// Busca por título "cardiologia" apenas do Brasil
[bvs_journals searchTitle="cardiologia" country="Brasil"]

// Journals de Enfermagem do Brasil com "saúde" no título
[bvs_journals country="Brasil" subject="Enfermagem" searchTitle="saúde"]
```

#### Parâmetros via URL (Query String)

O shortcode também aceita parâmetros através da URL, permitindo criar links diretos para buscas específicas:

**Parâmetros disponíveis na URL:**

| Parâmetro URL | Mapeia para | Exemplo |
|---------------|-------------|---------|
| `bvsCountry` | `country` | `?bvsCountry=Brasil` |
| `bvsSubject` | `subject` | `?bvsSubject=Medicina` |
| `bvsSearch` | `search` | `?bvsSearch=cardiologia` |
| `bvsSearchTitle` ou `bvsTitle` | `searchTitle` | `?bvsTitle=saúde+pública` |
| `bvsIssn` | `issn` | `?bvsIssn=1234-5678` |
| `bvsLimit` | `limit` | `?bvsLimit=20` |
| `bvsMax` | `max` | `?bvsMax=100` |
| `bvsPage` | `page` | `?bvsPage=2` |

**Exemplos de URLs:**

```
https://seusite.com/journals/?bvsCountry=Brasil&bvsTemplate=grid
https://seusite.com/journals/?bvsTitle=medicina&bvsLimit=20
https://seusite.com/journals/?bvsSubject=Enfermagem&bvsPage=2
```

**Uso:**
Coloque o shortcode `[bvs_journals]` em uma página, e os parâmetros da URL serão aplicados automaticamente. Os parâmetros da URL **sobrescrevem** os parâmetros do shortcode.

#### Filtros Interativos com Sidebar

Ative o parâmetro `showFilters="true"` para exibir uma sidebar com filtros interativos:

```php
[bvs_journals showFilters="true"]
```

**Funcionalidades da Sidebar:**
- ✅ Campo de busca por título
- ✅ Checkboxes de países (populados dinamicamente da API)
- ✅ Seleção múltipla de países (OR)
- ✅ Botões "Buscar" e "Limpar"
- ✅ Tags de filtros ativos com opção de remoção
- ✅ Responsivo (20% da largura em desktop, full width em mobile)

**Parâmetros URL da Sidebar:**
- `bvsTitle` - Filtro por título
- `bvsCountries[]` - Array de países selecionados (permite múltiplos)

**Exemplo de URL com múltiplos países:**
```
https://seusite.com/journals/?bvsCountries[]=Brasil&bvsCountries[]=Argentina
```

### [bvs_web_resources]

Exibe recursos web (bases de dados, portais, sites) da LIS/BVS Saúde.

#### Parâmetros de Filtragem

| Parâmetro | Descrição | Exemplo |
|-----------|-----------|---------|
| `country` | Filtrar por país | `country="Brasil"` |
| `subject` | Filtrar por assunto | `subject="Enfermagem"` |
| `type` | Filtrar por tipo | `type="database"` |
| `term` | Busca livre | `term="covid"` |
| `searchTitle` | Buscar por título | `searchTitle="biblioteca"` |

#### Parâmetros de Visualização

| Parâmetro | Descrição | Padrão |
|-----------|-----------|--------|
| `count` | Itens por página | `12` |
| `max` | Máximo de itens total | `50` |
| `show_pagination` | Habilitar paginação | `false` |
| `columns` | Número de colunas (grid) | `4` |
| `show_fields` | Campos a exibir | `title,type,country` |
| `showFilters` | Mostrar sidebar de filtros | `false` |

#### Exemplos de Uso

```php
// Grid com recursos do Brasil
[bvs_web_resources country="Brasil" max="20"]

// Com sidebar de filtros interativos
[bvs_web_resources showFilters="true"]

// Busca por termo com paginação
[bvs_web_resources term="saúde pública" count="10" show_pagination="true"]

// Busca por tipo específico
[bvs_web_resources type="database" max="15"]

// Filtros combinados (AND) - Bases de dados do Brasil
[bvs_web_resources country="Brasil" type="database" max="30"]

// Com filtros de título e país
[bvs_web_resources searchTitle="biblioteca" country="Brasil"]
```

#### Parâmetros via URL

| Parâmetro URL | Mapeia para | Exemplo |
|---------------|-------------|---------|
| `bvsCountry` | `country` | `?bvsCountry=Brasil` |
| `bvsSubject` | `subject` | `?bvsSubject=Medicina` |
| `bvsTerm` | `term` | `?bvsTerm=covid` |
| `bvsType` | `type` | `?bvsType=database` |
| `bvsTitle` ou `bvsSearchTitle` | `searchTitle` | `?bvsTitle=biblioteca` |
| `bvsCountries[]` | Array de países | `?bvsCountries[]=Brasil&bvsCountries[]=Peru` |

## 🔌 Parâmetros via URL (Query String)

> **⚠️ Prioridade:** Os parâmetros da URL sempre sobrescrevem os do shortcode

Ambos os shortcodes (`[bvs_journals]` e `[bvs_web_resources]`) aceitam parâmetros via URL, permitindo criar buscas dinâmicas e links diretos.


#### Parâmetros via URL (Query String)

O shortcode também aceita parâmetros através da URL:

**Parâmetros disponíveis na URL:**

| Parâmetro URL | Mapeia para | Exemplo |
|---------------|-------------|---------|
| `bvsCountry` | `country` | `?bvsCountry=Brazil` |
| `bvsSubject` | `subject` | `?bvsSubject=Medicina` |
| `bvsType` | `type` | `?bvsType=database` |
| `bvsTerm` | `term` | `?bvsTerm=covid` |
| `bvsSearchTitle` ou `bvsTitle` | `searchTitle` | `?bvsTitle=biblioteca+virtual` |
| `bvsCount` | `count` | `?bvsCount=20` |

**Exemplos de URLs:**

```
https://seusite.com/recursos/?bvsCountry=Brazil&bvsType=database
https://seusite.com/recursos/?bvsTitle=biblioteca&bvsCount=15
https://seusite.com/recursos/?bvsSubject=Enfermagem&bvsTerm=covid
```

**Uso:**
Coloque o shortcode `[bvs_web_resources]` em uma página, e os parâmetros da URL serão aplicados automaticamente. Os parâmetros da URL **sobrescrevem** os parâmetros do shortcode.

### [bvs_legislations]

Exibe legislações (leis, decretos, normas) da BVS Saúde.

#### Parâmetros de Filtragem

| Parâmetro | Descrição | Exemplo |
|-----------|-----------|---------|
| `country` | Filtrar por país | `country="Brasil"` |
| `subject` | Filtrar por área temática | `subject="Saúde Pública"` |
| `search` | Busca livre | `search="covid"` |
| `searchTitle` | Buscar por título | `searchTitle="lei orgânica"` |
| `type` | Filtrar por tipo | `type="decreto"` |

#### Parâmetros de Configuração

| Parâmetro | Descrição | Padrão |
|-----------|-----------|--------|
| `limit` | Itens por página | `12` |
| `max` | Máximo de itens total | `50` |
| `show_pagination` | Habilitar paginação | `false` |
| `page` | Página inicial | `1` |
| `show_fields` | Campos a exibir | `title,type,country,scope` |
| `showFilters` | Mostrar sidebar de filtros | `false` |

#### Exemplos de Uso

```php
// Grid com legislações do Brasil
[bvs_legislations country="Brasil" max="20"]

// Busca por tipo específico
[bvs_legislations type="lei" max="15"]

// Busca por título
[bvs_legislations searchTitle="saúde pública" limit="10"]

// Filtros combinados
[bvs_legislations country="Brasil" type="decreto" max="30"]
```

### [bvs_bibliographic_databases] / [bvs_databases]

Exibe bases bibliográficas da BVS Saúde.

#### Parâmetros de Filtragem

| Parâmetro | Descrição | Exemplo |
|-----------|-----------|---------|
| `country` | Filtrar por país | `country="Brasil"` |
| `subject` | Filtrar por área temática | `subject="Medicina"` |
| `search` | Busca livre | `search="cardiologia"` |
| `searchTitle` | Buscar por título | `searchTitle="medline"` |
| `type` | Filtrar por tipo | `type="bibliographic"` |

#### Parâmetros de Configuração

| Parâmetro | Descrição | Padrão |
|-----------|-----------|--------|
| `limit` | Itens por página | `12` |
| `max` | Máximo de itens total | `50` |
| `show_pagination` | Habilitar paginação | `false` |
| `page` | Página inicial | `1` |
| `show_fields` | Campos a exibir | `title,type,country,author` |
| `showFilters` | Mostrar sidebar de filtros | `false` |

#### Exemplos de Uso

```php
// Grid com bases do Brasil
[bvs_databases country="Brasil" max="20"]

// Busca por título
[bvs_bibliographic_databases searchTitle="pubmed" limit="10"]

// Filtros combinados
[bvs_databases country="Brasil" subject="Saúde" max="30"]
```

## ⚠️ Shortcodes com Problemas Conhecidos

### [bvs_events] e [bvs_multimedia]

Estes shortcodes estão implementados mas apresentam problemas na integração com a API BVS:

- **Problema**: Chamadas incorretas para os endpoints da API
- **Sintoma**: Erros de conexão e timeouts
- **Status**: Requer correção na configuração dos endpoints da API
- **Solução**: Aguardando correção dos URLs dos endpoints na configuração do plugin

**Não recomendado para uso em produção até correção dos problemas de API.**

## 🔧 API Client

O plugin fornece uma classe `BvsaludClient` para interagir com a API BVS.

### Métodos Disponíveis

#### Para Journals

```php
use BV\API\BvsaludClient;

$client = new BvsaludClient();

// Busca geral
$results = $client->searchJournals(['q' => 'medicina', 'count' => 20]);

// Por país
$journals = $client->getJournalsByCountry('Brasil', 10);

// Por assunto
$journals = $client->getJournalsBySubject('Medicina', 15);

// Por título
$journals = $client->getJournalsByTitle('saúde pública', 10);

// Por ISSN
$journal = $client->getJournalByIssn('1234-5678');

// Listagem com paginação
$results = $client->listJournals(1, 20); // página, por_página
```

#### Para Recursos Web

```php
$client = new BvsaludClient();

// Busca geral
$results = $client->searchWebResources(['q' => 'biblioteca', 'count' => 20]);

// Por país
$resources = $client->getWebResourcesByCountry('Brazil', 10);

// Por assunto
$resources = $client->getWebResourcesBySubject('Medicina', 15);

// Por tipo
$resources = $client->getWebResourcesByType('database', 10);

// Por título
$resources = $client->getWebResourcesByTitle('biblioteca virtual', 10);

// Por termo
$resources = $client->searchWebResourcesByTerm('covid', 20);

// Listagem geral
$results = $client->listWebResources(1, 20);
```

#### Para Legislações

```php
$client = BvsaludClient::forLegislations();

// Busca geral
$results = $client->searchLegislations(['q' => 'lei orgânica', 'count' => 20]);

// Por país
$legislations = $client->getLegislationsByCountry('Brasil', 10);

// Por assunto
$legislations = $client->getLegislationsBySubject('Saúde Pública', 15);

// Por título
$legislations = $client->getLegislationsByTitle('lei orgânica', 10);

// Por tipo
$legislations = $client->getLegislationsByType('decreto', 10);

// Listagem com paginação
$results = $client->listLegislations(1, 20);
```

#### Para Bases Bibliográficas

```php
$client = new BvsaludClient();

// Busca geral
$results = $client->searchBibliographicDatabases(['q' => 'medline', 'count' => 20]);

// Por país
$databases = $client->getBibliographicDatabasesByCountry('Brasil', 10);

// Listagem geral
$results = $client->listBibliographicDatabases(1, 20);
```

#### Métodos de Teste de Conexão

```php
// Teste geral
$test = $client->testConnection();

// Teste específico para legislações
$test = $client->testLegislationsConnection();

// Teste específico para bases bibliográficas
$test = $client->testBibliographicDatabasesConnection();
```

#### ⚠️ Métodos com Problemas Conhecidos

```php
// Estes métodos estão implementados mas apresentam problemas de API:
// $client->searchEvents() - Erro de conexão
// $client->searchMultimedia() - Erro de conexão
// $client->testEventsConnection() - Falha na conexão
// $client->testMultimediaConnection() - Falha na conexão
```

## 📁 Estrutura do Projeto

```
api-consumer-wp-plugin/
├── src/
│   ├── Admin/              # Painel administrativo
│   │   ├── AdminMenu.php
│   │   └── SettingsPage.php
│   ├── API/                # Cliente e DTOs da API
│   │   ├── BvsaludClient.php
│   │   ├── BibliographicDatabaseDto.php
│   │   ├── EventDto.php
│   │   ├── JournalDto.php
│   │   ├── LegislationDto.php
│   │   ├── MultimediaDto.php
│   │   └── WebResourceDto.php
│   ├── Assets/             # CSS e JavaScript
│   │   ├── admin.css
│   │   ├── admin.js
│   │   ├── public.css
│   │   └── public.js
│   ├── Shortcodes/         # Shortcodes
│   │   ├── BvsBibliographicDatabasesShortcode.php
│   │   ├── BvsEventsShortcode.php
│   │   ├── BvsJournalsShortcode.php
│   │   ├── BvsLegislationsShortcode.php
│   │   ├── BvsMultimediaShortcode.php
│   │   └── BvsWebResourcesShortcode.php
│   ├── Support/            # Helpers e utilitários
│   │   ├── Cache.php
│   │   ├── Helpers.php
│   │   └── ResourceCardDto.php
│   ├── Templates/          # Templates de exibição
│   │   ├── bvs-grid.php
│   │   └── components/
│   │       └── resource-card.php
│   ├── Autoloader.php
│   └── Plugin.php
├── bvsalud-integrator.php  # Arquivo principal do plugin
├── debug_multimedia.php    # Script de debug para multimídia
├── uninstall.php           # Script de desinstalação
├── .editorconfig           # Configuração do editor
├── .php-cs-fixer.dist.php  # Configuração do PHP CS Fixer
├── .prettierrc             # Configuração do Prettier
├── .vscode/                # Configurações do VS Code
├── languages/              # Arquivos de tradução
└── README.md               # Este arquivo
```

## 🚀 Desenvolvimento

### Requisitos

- PHP 7.4+
- WordPress 5.0+
- Composer (opcional)

### Padrões de Código

- PSR-4 para autoloading
- PSR-12 para coding standards
- Namespaces: `BV\*`
- Classes finais para DTOs e Shortcodes

### Segurança

- ✅ Sanitização de inputs com `sanitize_text_field()`
- ✅ Escape de outputs com `esc_html()`, `esc_url()`, `esc_attr()`
- ✅ Verificação de `ABSPATH` em todos os arquivos
- ✅ Prepared statements para queries
- ✅ Nonces para formulários

### Cache

O plugin implementa um sistema de cache opcional para otimizar requisições à API:

```php
use BV\Support\Cache;

$cache = new Cache();
$data = $cache->get('chave');
if (!$data) {
    $data = $client->searchJournals();
    $cache->set('chave', $data, 3600); // 1 hora
}
```