# EcoLink

> Projeto dedicado a reunir organizadores e participantes em eventos ecológicos.
[Link do Site](http://ecolink.page.gd/) 

O EcoLink é uma plataforma que tem como objetivo conectar pessoas interessadas em preservação ambiental e sustentabilidade. Através da plataforma, organizadores podem divulgar suas iniciativas e participantes podem facilmente encontrar eventos ecológicos para colaborar.

## Participantes
Yuri Gabriel Ramos Dalcin, Guilherme Ryu Bertoluci, Raul Alves Cordeiro Macena e Vitor Pietro Santos Mota
---

## Configuração do ambiente

Como o projeto atualmente é baseado em tecnologias web estáticas, a configuração do ambiente de desenvolvimento é muito simples e rápida.

**Pré-requisitos:**
- Um navegador web moderno (Google Chrome, Firefox, Edge, etc.).
- Um editor de código de sua preferência (recomendamos o Visual Studio Code).

**Passo a passo para rodar localmente:**

1. Clone o repositório para a sua máquina:
   git clone https://github.com/Yuuh15/projeto_EcoLink.git

2. Acesse a pasta do projeto:
   cd projeto_EcoLink

3. Abra o projeto no seu editor de código.

4. Para visualizar a aplicação, basta dar um duplo clique no arquivo `index.html` para abri-lo no seu navegador.
   - Observação: Se estiver usando o VS Code, recomendamos a extensão Live Server para atualizar a página automaticamente a cada alteração salva.

---

## Desenvolvimento Front-end

A interface e as interações com o usuário foram construídas utilizando as tecnologias base da Web, com foco na simplicidade e acessibilidade.

- **HTML5:** Estruturação semântica das páginas do projeto (`index.html` para a página principal e `login.html` para autenticação).
- **CSS3:** Estilização das páginas, garantindo uma interface agradável e responsiva (`index.css` e `login.css`).
- **JavaScript (Vanilla):** Lógica de interações dinâmicas na interface (`script.js`).

---

## Desenvolvimento Back-end

No momento, este projeto é focado inteiramente no Front-end.

Não há lógica de Back-end implementada nesta versão do repositório. As validações de formulário (se existirem) são feitas no lado do cliente com JavaScript. Em versões futuras, pode ser implementado um servidor usando tecnologias como Node.js, Python ou Java para gerenciar usuários e eventos.

---

## Banco de dados

O sistema utiliza o MySQL como Sistema de Gerenciamento de Banco de Dados (SGBD) relacional para armazenar e estruturar as informações da aplicação, como registros de usuários, dados de organizadores e detalhes dos eventos ecológicos.

Para a administração e controle do banco de dados, o projeto conta com uma implementação do phpMyAdmin. Essa integração permite gerenciar as tabelas, executar consultas SQL e realizar a manutenção geral dos dados através de uma interface web gráfica e intuitiva, facilitando o processo de desenvolvimento e testes.

---

## Integrações

Sem integrações ativas no momento.

No futuro, o projeto EcoLink poderá contar com integrações valiosas como:
- **API de Mapas (Google Maps ou Mapbox):** Para geolocalização exata dos eventos ecológicos.
- **APIs de Redes Sociais:** Para facilitar o login (Google/Facebook/GitHub) e o compartilhamento de eventos.
- **Serviços de E-mail:** Para confirmação de cadastro e lembretes aos participantes dos eventos.


