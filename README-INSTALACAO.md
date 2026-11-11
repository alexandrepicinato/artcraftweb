# ArtCraft Suite — pacote completo para `public_html`

## Instalação: apenas extrair e acessar

Este ZIP contém **o painel PHP e todos os 13 editores WebAssembly já compilados**, além do Font Studio em JavaScript. Não há instalador de módulos, download de ZIPs depois da publicação, Cargo, Rust, Node.js, Docker ou serviços de conversão de arquivos no servidor.

1. No gerenciador de arquivos do seu painel de hospedagem, envie o ZIP único desta entrega.
2. Extraia **todo o conteúdo** diretamente em `public_html/` ou numa subpasta, por exemplo `public_html/craftsuite/`. A estrutura deve conter `index.php`, `assets/`, `modules/` e `legal/` na mesma pasta.
3. Acesse `https://seu-dominio.com/` (ou `https://seu-dominio.com/craftsuite/`). No primeiro acesso, defina a senha administrativa para proteger o painel.
4. Abra qualquer editor pela barra lateral. Os arquivos WASM, scripts e recursos já estão em `modules/<editor>/`. **Não há mais etapa de instalação ou upload de módulos.**

**Atenção ao disco:** reserve ao menos 650 MB de espaço depois de extrair; são aproximadamente 541 MiB de arquivos dos 13 aplicativos (sem cópias opcionais pré-comprimidas), além do painel, da biblioteca de arquivos e de cotas temporárias do gerenciador de ZIP. Reserve idealmente 1,2–1,5 GiB livre durante a extração, pois o ZIP pode coexistir com os arquivos extraídos.

## Requisitos da hospedagem

- Apache 2.4 com `.htaccess`, PHP **8.1+**, módulo `mod_mime`, HTTPS e permissão de escrita para a pasta de dados privados.
- O servidor precisa entregar `.wasm` com tipo `application/wasm`, `.js` com `text/javascript` e fontes `.ttf` como `font/ttf`. O arquivo `.htaccess` inclui as configurações correspondentes.
- Não são necessários `curl`, `ZipArchive`, acesso externo ao GitHub nem compilador Rust para executar os módulos. A extensão `fileinfo` pode auxiliar no upload de documentos; não é necessária para abrir editores.
- Navegador atualizado de 64 bits com suporte a WebAssembly e, para algumas funções gráficas, WebGL2/WebGPU. Memória disponível no dispositivo é especialmente importante para editores de fotos, efeitos e vídeo.
- Em hospedagens com Nginx, configure os tipos MIME no servidor, pois `.htaccess` não é aplicado pelo Nginx.

## Módulos já incluídos

| Área | Aplicativos |
|---|---|
| Documentos | PDFCraft, WordCraft, DeckCraft, DesignCraft |
| Design | VectorCraft, FigCraft, PhotoCraft, LightCraft, Font Studio |
| Vídeo e áudio | EffectCraft, FilmCraft, SoundCraft |
| 3D e materiais | MaterialCraft, TextureCraft |

Font Studio é um pré-visualizador de fontes em JavaScript. **Não é o serviço Rust font-preview-service**. O pacote effectcraft-corpus é uma suíte de dados/testes, não um editor web, e por isso não está na barra lateral.

## Estrutura

- `index.php`, `bootstrap.php`, `catalog.php`: painel, login e navegação; `files.php`: biblioteca protegida de documentos.
- `assets/`: interface e ícones.
- `modules/<editor>/`: **distribuições web originais já extraídas**. Cada pasta possui seu `index.html`, arquivos WASM/JS e demais recursos, podendo ser acessada diretamente.
- `modules/fontstudio/`: ferramenta local de prévia de fontes.
- `MANIFEST-VERSOES.json`, `BUNDLED-MODULES.json`: versões e checksums dos releases web incorporados.
- `legal/licenses/`: avisos de licença das bases originais.

## Funcionamento e limites da integração

Os editores são as versões web dos projetos originais reunidas por um painel PHP com uma única barra lateral e acesso a uma biblioteca privada. **Não são um único programa monolítico**: cada editor executa seu próprio núcleo WASM, mantém sua experiência e formatos e não sincroniza projetos automaticamente com os demais. A biblioteca PHP não salva automaticamente documentos abertos nos editores; use os comandos internos de importar/salvar/exportar.

O painel requer senha, mas **os arquivos estáticos dentro de `modules/` não exigem autenticação por padrão**. Se a intenção for restringir o acesso a todos os editores, configure autenticação por diretório no servidor para toda a instalação. Dados privados e biblioteca são protegidos pela aplicação.

Alguns recursos avançados podem exigir HTTPS e permissões do navegador ou capacidades de hardware; eles não dependem de um serviço de build na hospedagem.

## Validação e suporte

- Todos os 13 arquivos web oficiais foram extraídos no pacote, depois de conferir os tamanhos e o SHA-256 dos ZIPs com o manifesto fixado em 06/11/2026.
- Os 14 módulos têm `modules/<nome>/index.html`.
- A navegação, o fluxo de senha e os endpoints PHP foram verificados localmente; não foi possível executar e testar manualmente todos os recursos internos de cada aplicativo em uma hospedagem real. O pacote está **pronto para implantação**, mas a compatibilidade final depende do servidor e navegador.
- Se um editor não abrir, verifique o console JavaScript, a resposta do arquivo `.wasm`, tipo MIME, HTTPS e limite de memória do navegador. Os detalhes de hospedagem originais estão em `modules/<editor>/HOSTING.md`.
- Não modifique a hierarquia interna de `modules/` após descompactar.

## Proveniência e licenças

Os motores web foram obtidos dos artefatos de build dos projetos `storytold/*`, com os checksums correspondentes aos releases oficiais. Licenças MIT/Apache-2.0 e avisos adicionais acompanham cada distribuição. O painel PHP foi criado para integração independente e não implica afiliação ao time ArtCraft.
