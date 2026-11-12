> **React port note:** Backend details below are adapted from the Svelte project and checked against the corresponding server implementation. React UI parity and deployment behavior have not been verified end to end; test the relevant flows in your environment.

# Upload validation

Upload rules belong to the feature that accepts the file. Define allowed file types and the maximum size in that feature's Laravel validation rules; do not assume a browser restriction protects the server. Laravel validation is authoritative.

Mirror the allowed types in the input's `accept` attribute to guide file selection. It is only a browser hint, not validation. Keep each input's policy independent, even when another input accepts a similar file.

Current example: admin branding uploads are validated in `ConfigurationController`. The logo accepts PNG, JPG, JPEG, and WebP up to 2 MB; the favicon accepts PNG and ICO up to 512 KB. The React configuration page currently uses `accept="image/*"` for both fields, which is broader than the server rules; the server remains authoritative.

For a future document upload, define that feature's own Laravel rules and matching `accept` value. Add a shared policy mechanism only if repeated policies become difficult to maintain.
