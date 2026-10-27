<!-- ring-master:start -->
## Ring Master

- Specs, product docs and local standards live in `docs/agent/`. Shared standards come from `vendor/` through `docs/agent/standards.index.yml`; read the matching standards before changing code.
- Workflow: `/ring-master-shape-spec` → `/ring-master-write-spec` → `/ring-master-create-tasks` → `/ring-master-implement` → `/ring-master-verify-spec`.
- Done means `composer test` passes. When work must break a standard, add a row to `docs/agent/standards-feedback.md`.
<!-- ring-master:end -->
