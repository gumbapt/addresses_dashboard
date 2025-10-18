# 🏗️ Arquitetura do Sistema de Relatórios - Diagrama Visual

## **Estrutura Atual vs Objetivo Final**

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           🎯 OBJETIVO FINAL                                    │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                 │
│  ┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐            │
│  │   📊 DOMÍNIO    │    │   🌐 GLOBAL     │    │   🔍 FILTROS    │            │
│  │   ESPECÍFICO    │    │   CROSS-DOMAIN  │    │   AVANÇADOS     │            │
│  │                 │    │                 │    │                 │            │
│  │ ✅ Dashboard    │    │ ❌ Ranking      │    │ ❌ Por Período  │            │
│  │ ✅ Agregação    │    │ ❌ Tecnologias  │    │ ❌ Por Status   │            │
│  │ ✅ Individual   │    │ ❌ Métricas    │    │ ❌ Por Tecnologia│           │
│  └─────────────────┘    └─────────────────┘    └─────────────────┘            │
│                                                                                 │
└─────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────┐
│                           🏗️ ARQUITETURA ATUAL                                 │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                 │
│  ┌─────────────────────────────────────────────────────────────────────────┐   │
│  │                           📥 SUBMISSÃO                                  │   │
│  │                                                                         │   │
│  │  POST /api/reports/submit          POST /api/reports/submit-daily      │   │
│  │  Headers: X-API-KEY                Headers: X-API-KEY                  │   │
│  │                                                                         │   │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐   │   │
│  │  │ Validação   │→ │ Criação     │→ │ ProcessJob  │→ │ Processamento│   │   │
│  │  │             │  │ Report      │  │             │  │             │   │   │
│  │  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘   │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
│                                                                                 │
│  ┌─────────────────────────────────────────────────────────────────────────┐   │
│  │                           🗄️ BANCO DE DADOS                             │   │
│  │                                                                         │   │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐   │   │
│  │  │   domains   │  │   reports   │  │report_summaries│report_providers│   │   │
│  │  │             │  │             │  │             │  │             │   │   │
│  │  │ id          │  │ id          │  │ report_id   │  │ report_id   │   │   │
│  │  │ name        │  │ domain_id   │  │ total_requests│ provider_id │   │   │
│  │  │ api_key     │  │ report_date │  │ success_rate│  │ technology │   │   │
│  │  │ status      │  │ status      │  │ avg_speed   │  │ total_count│   │   │
│  │  │ raw_data    │  │             │  │             │  │ success_rate│   │   │
│  │  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘   │   │
│  │                                                                         │   │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐   │   │
│  │  │report_states│  │report_cities│  │report_zipcodes│  │  providers  │   │   │
│  │  │             │  │             │  │             │  │             │   │   │
│  │  │ report_id   │  │ report_id   │  │ report_id   │  │ id          │   │   │
│  │  │ state_id    │  │ city_id     │  │ zipcode_id  │  │ name        │   │   │
│  │  │ request_count│ │ request_count│ │ request_count│ │ slug        │   │   │
│  │  │ percentage  │  │ percentage  │  │ percentage  │  │ technology │   │   │
│  │  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘   │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
│                                                                                 │
│  ┌─────────────────────────────────────────────────────────────────────────┐   │
│  │                           📤 VISUALIZAÇÃO                               │   │
│  │                                                                         │   │
│  │  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐       │   │
│  │  │   Individual    │  │   Por Domínio    │  │   Cross-Domain  │       │   │
│  │  │                 │  │                 │  │                 │       │   │
│  │  │ GET /reports/{id}│  │ GET /domain/{id}│  │ GET /global/*   │       │   │
│  │  │                 │  │ /dashboard      │  │                 │       │   │
│  │  │ ✅ Implementado │  │ ✅ Implementado │  │ ❌ Pendente     │       │   │
│  │  │                 │  │                 │  │                 │       │   │
│  │  │ GetReportWith   │  │ GetDashboard    │  │ GetGlobal*      │       │   │
│  │  │ StatsUseCase    │  │ DataUseCase     │  │ UseCase         │       │   │
│  │  └─────────────────┘  └─────────────────┘  └─────────────────┘       │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
│                                                                                 │
└─────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────┐
│                           🚧 FUNCIONALIDADES PENDENTES                        │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                 │
│  ┌─────────────────────────────────────────────────────────────────────────┐   │
│  │                           🌐 CROSS-DOMAIN                               │   │
│  │                                                                         │   │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐   │   │
│  │  │   Ranking   │  │ Tecnologias │  │   Métricas  │  │   Filtros   │   │   │
│  │  │   Domínios  │  │   Globais   │  │   Globais   │  │   Avançados │   │   │
│  │  │             │  │             │  │             │  │             │   │   │
│  │  │ ❌ Pendente │  │ ❌ Pendente │  │ ❌ Pendente │  │ ❌ Pendente │   │   │
│  │  │             │  │             │  │             │  │             │   │   │
│  │  │ GET /global/│  │ GET /global/│  │ GET /global/│  │ ?date_from= │   │   │
│  │  │ domain-     │  │ technology- │  │ metrics     │  │ ?technology= │   │   │
│  │  │ ranking     │  │ analysis    │  │             │  │ ?status=    │   │   │
│  │  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘   │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
│                                                                                 │
│  ┌─────────────────────────────────────────────────────────────────────────┐   │
│  │                           🗄️ NOVAS TABELAS                              │   │
│  │                                                                         │   │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐   │   │
│  │  │domain_      │  │global_      │  │global_      │  │cache_       │   │   │
│  │  │rankings     │  │technology_  │  │metrics      │  │aggregations │   │   │
│  │  │             │  │stats        │  │             │  │             │   │   │
│  │  │ domain_id   │  │ technology  │  │ total_      │  │ key         │   │   │
│  │  │ period_start│  │ period_start│  │ requests    │  │ data        │   │   │
│  │  │ period_end  │  │ period_end  │  │ success_   │  │ expires_at  │   │   │
│  │  │ total_      │  │ total_      │  │ rate       │  │             │   │   │
│  │  │ requests    │  │ requests    │  │ domain_    │  │             │   │   │
│  │  │ rank_       │  │ domain_     │  │ count      │  │             │   │   │
│  │  │ position    │  │ count       │  │             │  │             │   │   │
│  │  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘   │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
│                                                                                 │
└─────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────┐
│                           📊 FLUXO DE DADOS                                    │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                 │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐     │
│  │   Cliente   │───▶│   API       │───▶│   Validação │───▶│   Criação   │     │
│  │   Submete   │    │   Endpoint  │    │             │    │   Report    │     │
│  └─────────────┘    └─────────────┘    └─────────────┘    └─────────────┘     │
│                                                                                 │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐     │
│  │ ProcessJob │◀───│   Queue     │◀───│   Job       │◀───│   Report    │     │
│  │            │    │   System    │    │   Dispatch  │    │   Created   │     │
│  └─────────────┘    └─────────────┘    └─────────────┘    └─────────────┘     │
│                                                                                 │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐     │
│  │ Processamento│───▶│   Inserção  │───▶│   Agregação │───▶│   Cache     │     │
│  │   Dados     │    │   Tabelas   │    │   Global    │    │   Update    │     │
│  │             │    │   Processadas│    │   (Futuro)  │    │   (Futuro) │     │
│  └─────────────┘    └─────────────┘    └─────────────┘    └─────────────┘     │
│                                                                                 │
└─────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────┐
│                           🎯 STATUS DE IMPLEMENTAÇÃO                           │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                 │
│  ✅ IMPLEMENTADO (60% do objetivo final)                                        │
│  ├── Submissão de relatórios (original + WordPress)                            │
│  ├── Visualização individual de relatórios                                    │
│  ├── Dashboard por domínio                                                     │
│  ├── Agregação por domínio                                                     │
│  ├── Processamento assíncrono                                                  │
│  ├── Validação e autenticação                                                 │
│  └── Estrutura de dados sólida                                                 │
│                                                                                 │
│  ❌ PENDENTE (40% do objetivo final)                                           │
│  ├── Ranking de domínios                                                       │
│  ├── Análise global de tecnologias                                            │
│  ├── Métricas globais                                                         │
│  ├── Filtros avançados                                                        │
│  ├── Cache de agregações                                                      │
│  └── Jobs de pré-cálculo                                                      │
│                                                                                 │
└─────────────────────────────────────────────────────────────────────────────────┘
