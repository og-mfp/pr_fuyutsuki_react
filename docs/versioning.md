> **React port note:** Backend details below are adapted from the Svelte project and checked against the corresponding server implementation. React UI parity and deployment behavior have not been verified end to end; test the relevant flows in your environment.

# Project versioning

The root `VERSION` file is the project version source. Versions follow Semantic Versioning (`MAJOR.MINOR.PATCH`):

- Patch for backward-compatible fixes.
- Minor for backward-compatible features.
- Major for breaking changes.

Before a release, update `VERSION`, commit the change, and create a matching Git tag (for example, `v0.1.0`). Laravel's config cache captures the value when configuration is cached, so rebuild it after changing the version.
