# 🚗 Sistema de Controle de Acesso e Estacionamento

![PHP](https://img.shields.io/badge/php-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/mysql-%2300f.svg?style=for-the-badge&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/javascript-%23F7DF1E.svg?style=for-the-badge&logo=javascript&logoColor=black)
![HTML5](https://img.shields.io/badge/html5-%23E34F26.svg?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/css3-%231572B6.svg?style=for-the-badge&logo=css3&logoColor=white)

Aplicação web Full-Stack desenvolvida para o gerenciamento inteligente de acesso de veículos e pedestres. O sistema integra o controle de catracas, emissão de tickets para visitantes e um portal financeiro para os estudantes.

## 📌 Funcionalidades Principais
Este sistema é dividido em módulos para atender diferentes perfis de usuários:

* **Portal do Estudante:** Área restrita para consulta de dados, extratos e sistema de **recarga de saldo** online.
* **Módulo de Visitantes:** Interface para cadastro rápido, geração de tickets e simulação de entrada em catracas/cancelas via API (`api_cancela.php`).
* **Dashboard Administrativo/Operador:** Painel de controle para monitoramento de entrada e saída, gerenciamento de tarifas e veículos, e exportação de relatórios/extratos.
* **Autenticação Segura:** Sistemas de login distintos para alunos, funcionários e administradores.

## 🛠️ Tecnologias e Arquitetura
* **Back-end:** PHP (Lógica de negócios, APIs de catraca e autenticação).
* **Banco de Dados:** Banco de dados relacional (Scripts SQL incluídos) acessado via conexão unificada (PDO/MySQL).
* **Front-end:** HTML5, CSS3, e JavaScript Vanilla para interatividade dinâmica e consumo das APIs internas (ex: `visitante_api.js`).

## 🚀 Como executar o projeto localmente
Como o projeto utiliza PHP, é necessário um servidor local (como XAMPP, WAMP ou Laragon):

1. Instale e inicie o servidor Apache e MySQL (ex: painel do XAMPP).
2. Clone este repositório para a pasta pública do seu servidor (no XAMPP, a pasta `htdocs`).
3. **Configuração do Banco de Dados:**
   - Acesse o *phpMyAdmin* (`http://localhost/phpmyadmin`).
   - Crie um banco de dados para o projeto.
   - Importe os arquivos SQL presentes na pasta `src/database/` (como o `verificar_dados_visitante.sql`).
   - Ajuste as credenciais de acesso no arquivo `src/frontend/PHP/conexao_unificada.php`.
4. Acesse o sistema pelo navegador: `http://localhost/nome-da-pasta/src/frontend/HTML/index.html`.

## 📂 Estrutura de Diretórios em Destaque
- `src/database/`: Scripts de banco de dados e verificações.
- `src/frontend/HTML/`: Telas da aplicação (Dashboards, Login, Portais).
- `src/frontend/PHP/`: Lógica de servidor, conexões e APIs internas (ex: `processar_recarga.php`, `exportar_extrato.php`).
- `src/frontend/js/` e `src/frontend/css/`: Lógica de interface, validações e estilização.

---

## 👤 Autor
**Luiz Henrique da Silva Pereira**
*Estudante de ADS - Uninassau | Desenvolvedor Full-Stack*

[![LinkedIn](https://img.shields.io/badge/linkedin-%230077B5.svg?style=for-the-badge&logo=linkedin&logoColor=white)](https://www.linkedin.com/in/luiz-henrique-da-silva-pereira-574620398)
[![GitHub](https://img.shields.io/badge/github-%23121011.svg?style=for-the-badge&logo=github&logoColor=white)](https://github.com/luiz-rick)
[![Instagram](https://img.shields.io/badge/instagram-%23E4405F.svg?style=for-the-badge&logo=instagram&logoColor=white)]((https://www.instagram.com/_rikjk_))
