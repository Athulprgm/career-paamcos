# MultiCred Documentation Index

Welcome to the technical documentation repository for the **MultiCred Deposit & Commission Management System**, engineered for **Pristine Agro Co-operative Society**.

This documentation suite is organized for software engineers, systems administrators, auditors, and AI coding agents.

---

## Documentation Structure

```
docs/
├── README.md                      # Documentation table of contents (this file)
├── PROJECT_OVERVIEW.md            # Vision, scope, personas, and key features
├── ARCHITECTURE.md                # System layers, data flows, and design diagrams
├── PROJECT_STRUCTURE.md           # Repository map and file-level guidance
├── MODULES.md                     # Detailed functional breakdown of all 13 modules
├── DATABASE.md                    # Data dictionary, relationships, schemas, and invariants
├── API.md                         # Comprehensive routing & web endpoint reference (215 routes)
├── BUSINESS_RULES.md              # Mathematical formulas, calculations, and financial invariants
├── SECURITY.md                    # Authentication, authorization, auditing, and hardening
├── TESTING.md                     # Test suites, execution commands, and QA guidelines
├── DEPLOYMENT.md                  # Infrastructure setup, environment configuration, and ops
├── TROUBLESHOOTING.md             # Common errors, diagnosis, and verified resolutions
├── AGENTS_REFERENCE.md            # Comprehensive operational reference for AI coding agents
│
├── development/
│   ├── CODING_STANDARDS.md        # PHP 8.4, Laravel, Pint, and design guidelines
│   └── DEVELOPMENT_WORKFLOW.md    # 11-step development process for safe implementation
│
└── decisions/
    └── README.md                  # Architecture Decision Records (ADRs 001–007)
```

---

## Core Documentation Map

| Document | Primary Audience | Key Topics Covered |
| :--- | :--- | :--- |
| [Project Overview](file:///c:/xampp8.2/htdocs/multicred-deposit-commission/docs/PROJECT_OVERVIEW.md) | All Stakeholders | Business context, user personas, high-level features, known limitations. |
| [Architecture](file:///c:/xampp8.2/htdocs/multicred-deposit-commission/docs/ARCHITECTURE.md) | Architects & Senior Devs | Multi-tier portal architecture, request lifecycle, data flow, state diagrams. |
| [Project Structure](file:///c:/xampp8.2/htdocs/multicred-deposit-commission/docs/PROJECT_STRUCTURE.md) | Developers & Agents | Directory hierarchy, file responsibilities, where to implement features. |
| [Modules](file:///c:/xampp8.2/htdocs/multicred-deposit-commission/docs/MODULES.md) | Feature Developers | 13 system modules: inputs, outputs, models, controllers, and services. |
| [Database Reference](file:///c:/xampp8.2/htdocs/multicred-deposit-commission/docs/DATABASE.md) | DBAs & Backend Devs | 38 tables, foreign keys, indexes, decimal safety, transactional boundaries. |
| [API Reference](file:///c:/xampp8.2/htdocs/multicred-deposit-commission/docs/API.md) | Backend & Integration | All 215 web and operational endpoints, request parameters, validation rules. |
| [Business Rules](file:///c:/xampp8.2/htdocs/multicred-deposit-commission/docs/BUSINESS_RULES.md) | Product & QA | Mathematical formulas for FD/RD/DD/MRP, multi-year commission logic, 12 ranks. |
| [Security & Audit](file:///c:/xampp8.2/htdocs/multicred-deposit-commission/docs/SECURITY.md) | Security & Compliance | Multi-identifier login, Argon2id hashing, role middleware, audit logging. |
| [Testing Guide](file:///c:/xampp8.2/htdocs/multicred-deposit-commission/docs/TESTING.md) | QA & Developers | Feature & Unit test suites (42 suites), test execution, Pint styling. |
| [Deployment & Ops](file:///c:/xampp8.2/htdocs/multicred-deposit-commission/docs/DEPLOYMENT.md) | DevOps & SysAdmins | Server requirements, deployment steps, background queues, caching. |
| [Troubleshooting](file:///c:/xampp8.2/htdocs/multicred-deposit-commission/docs/TROUBLESHOOTING.md) | Support & On-Call | Verified fixes for database, auth, asset bundling, and runtime errors. |
| [Coding Standards](file:///c:/xampp8.2/htdocs/multicred-deposit-commission/docs/development/CODING_STANDARDS.md) | Developers | PHP 8.4 rules, strict typing, Laravel Pint, Blade design tokens. |
| [Development Workflow](file:///c:/xampp8.2/htdocs/multicred-deposit-commission/docs/development/DEVELOPMENT_WORKFLOW.md) | Developers & AI Agents | 11-step structured implementation cycle to prevent regressions. |
| [Architecture Decisions](file:///c:/xampp8.2/htdocs/multicred-deposit-commission/docs/decisions/README.md) | Architects & Leads | ADRs covering authentication, multi-year payout, lead conversion, and services. |
| [Agent Reference](file:///c:/xampp8.2/htdocs/multicred-deposit-commission/docs/AGENTS_REFERENCE.md) | AI Coding Agents | Operational rules, prohibited actions, code inspection order, and verification. |

---

## Quick Navigation Links

- Root Instructions & Rules: [AGENTS.md](file:///c:/xampp8.2/htdocs/multicred-deposit-commission/AGENTS.md)
- Root Overview: [README.md](file:///c:/xampp8.2/htdocs/multicred-deposit-commission/README.md)
- Contributing Guidelines: [CONTRIBUTING.md](file:///c:/xampp8.2/htdocs/multicred-deposit-commission/CONTRIBUTING.md)
- Release History: [CHANGELOG.md](file:///c:/xampp8.2/htdocs/multicred-deposit-commission/CHANGELOG.md)
