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
- 📱 **Templates responsivos**: grid, lista, compacto, detalhado
- 📄 **Paginação integrada**: navegação fácil entre resultados
- 🎨 **Customizável**: CSS e templates flexíveis
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
| `[bvs_events]` | Eventos em saúde | 🚧 Placeholder |
| `[bvs_multimedia]` | Vídeos, áudios, imagens | 🚧 Placeholder |
| `[bvs_legislations]` | Leis, decretos, normas | 🚧 Placeholder |
| `[bvs_databases]` ou `[bvs_bibliographic_databases]` | Bases bibliográficas | 🚧 Placeholder |

**Nota:** Shortcodes com status "Placeholder" exibem uma interface que indica a configuração está pronta, mas requerem implementação da integração com a API específica.

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
| `template` | Layout de exibição | `grid` |
| `columns` | Número de colunas | `3` |
| `show_fields` | Campos a exibir | `title,issn,publisher,country` |
| `showFilters` | Mostrar sidebar de filtros | `false` |

**Nota:** O template padrão é `grid` (grade de cards responsiva). O parâmetro `template` existe para compatibilidade futura.

#### Exemplos de Uso

```php
// Grid básico com journals do Brasil
[bvs_journals country="Brasil" max="20"]

// Com sidebar de filtros interativos (checkboxes de países)
[bvs_journals showFilters="true" columns="3"]

// Grid de 4 colunas com paginação
[bvs_journals limit="12" show_pagination="true" columns="4"]

// Busca por título
[bvs_journals searchTitle="saúde pública" limit="15"]

// Busca por ISSN específico
[bvs_journals issn="1234-5678"]

// Grid com 3 colunas e filtros ativos
[bvs_journals country="Argentina" columns="3" max="30" showFilters="true"]

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
| `bvsTemplate` | `template` | `?bvsTemplate=grid` |
| `bvsColumns` | `columns` | `?bvsColumns=3` |
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
[bvs_journals showFilters="true" template="grid" columns="3"]
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
| `template` | Layout de exibição | `default` |
| `columns` | Número de colunas (grid) | `4` |
| `show_fields` | Campos a exibir | `title,type,country` |
| `showFilters` | Mostrar sidebar de filtros | `false` |

#### Exemplos de Uso

```php
// Grid com recursos do Brasil
[bvs_web_resources country="Brasil" max="20" template="grid"]

// Com sidebar de filtros interativos
[bvs_web_resources showFilters="true" template="grid" columns="3"]

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
| `bvsTemplate` | `template` | `?bvsTemplate=compact` |

**Exemplos de URLs:**

```
https://seusite.com/recursos/?bvsCountry=Brazil&bvsType=database
https://seusite.com/recursos/?bvsTitle=biblioteca&bvsCount=15
https://seusite.com/recursos/?bvsSubject=Enfermagem&bvsTerm=covid
```

**Uso:**
Coloque o shortcode `[bvs_web_resources]` em uma página, e os parâmetros da URL serão aplicados automaticamente. Os parâmetros da URL **sobrescrevem** os parâmetros do shortcode.

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

## 📁 Estrutura do Projeto

```
api-consumer-wp-plugin/
├── src/
│   ├── Admin/              # Painel administrativo
│   │   ├── AdminMenu.php
│   │   └── SettingsPage.php
│   ├── API/                # Cliente e DTOs da API
│   │   ├── BvsaludClient.php
│   │   ├── JournalDto.php
│   │   └── WebResourceDto.php
│   ├── Assets/             # CSS e JavaScript
│   │   ├── admin.css
│   │   ├── admin.js
│   │   ├── public.css
│   │   └── public.js
│   ├── Shortcodes/         # Shortcodes
│   │   ├── BvsJournalsShortcode.php
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
├── uninstall.php           # Script de desinstalação
├── readme.txt              # README do WordPress.org
├── README.md               # Este arquivo
└── WEB_RESOURCES_USAGE.md  # Documentação detalhada
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